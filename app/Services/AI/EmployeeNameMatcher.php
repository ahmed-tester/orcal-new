<?php

namespace App\Services\AI;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\CompanyFormService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Matches employee names coming from imported statements against the names
 * stored in the system.
 *
 * Statements usually write a name as "محمد بابول حسين MD BABUL" (Arabic plus a
 * Latin transliteration), while the system stores only one of the two forms,
 * so a plain LIKE search almost never matches. Matching is intentionally
 * strict: when two employees score equally the name is reported as unmatched
 * instead of attaching money to the wrong person.
 */
class EmployeeNameMatcher
{
    protected const MIN_SCORE = 60;

    protected const STOP_WORDS = ['بن', 'بنت', 'ابن', 'ابو', 'ابي', 'عبد', 'ال'];

    /** @var array<int, array{id: int, arabic: string, latin: string}>|null */
    protected static ?array $employees = null;

    protected static ?string $cachedPasswordHash = null;

    protected static function getCachedPasswordHash(): string
    {
        return self::$cachedPasswordHash ??= Hash::make(Str::random(24));
    }

    public static function find(string $identifier): ?Employee
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        // 1. Numeric match (id, employee number, national id, iqama number)
        if (is_numeric($identifier)) {
            $employee = Employee::where(fn ($q) => $q->where('id', $identifier)->orWhere('employee_number', $identifier))
                ->orWhereHas('detail', fn ($q) => $q->where('national_id', $identifier)->orWhere('iqama_number', $identifier))
                ->first();

            if ($employee) {
                return $employee;
            }
        }

        // 2. Direct exact match on User name
        $direct = Employee::whereHas('user', fn ($q) => $q->where('name', $identifier))->first();
        if ($direct) {
            return $direct;
        }

        // 3. Fuzzy matching
        $needle = self::normalize($identifier);

        if ($needle['arabic'] === '' && $needle['latin'] === '') {
            return null;
        }

        $needleArabicTokens = self::tokens($needle['arabic']);
        $needleLatinTokens = self::tokens($needle['latin']);

        $bestId = null;
        $bestScore = 0;
        $isTie = false;

        foreach (self::employees() as $employee) {
            $score = max(
                self::score($needle['arabic'], $needleArabicTokens, $employee['arabic']),
                self::score($needle['latin'], $needleLatinTokens, $employee['latin']),
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $employee['id'];
                $isTie = false;
            } elseif ($score === $bestScore && $score > 0) {
                $isTie = true;
            }
        }

        if ($bestId === null || $isTie || $bestScore < self::MIN_SCORE) {
            return null;
        }

        return Employee::find($bestId);
    }

    /**
     * Find an existing employee or automatically create one if missing.
     */
    public static function findOrCreate(string $identifier, ?int $companyId = null, ?string $companyName = null): Employee
    {
        $employee = self::find($identifier);

        if ($employee) {
            return $employee;
        }

        // If companyName provided, resolve or create company
        if (! empty($companyName)) {
            $companyRes = CompanyFormService::resolveOrCreateCompanyByName($companyName, $companyId);
            if (! empty($companyRes['company_id'])) {
                $companyId = (int) $companyRes['company_id'];
            }
        }

        $companyId ??= Company::first()?->id;

        // Smart detection of user type & ID from identifier
        $cleanId = preg_replace('/\D/', '', $identifier);
        $idParam = (strlen($cleanId) === 10) ? $cleanId : null;
        $userType = UserType::detect(nationalId: $idParam, name: $identifier);
        $isSaudi = ($userType === UserType::SAUDI_EMPLOYEE);

        $userEmail = 'emp_'.Str::random(8).'@company.local';
        $user = User::create([
            'name' => trim($identifier),
            'email' => $userEmail,
            'phone' => null,
            'password' => self::getCachedPasswordHash(),
            'user_type' => $userType,
            'status' => 'active',
        ]);

        $newEmployee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'basic_salary' => 0.0,
            'status' => GeneralStatus::ACTIVE,
        ]);

        EmployeeDetail::create([
            'employee_id' => $newEmployee->id,
            'national_id' => ($isSaudi && $idParam) ? $idParam : null,
            'iqama_number' => (! $isSaudi && $idParam) ? $idParam : null,
            'nationality' => $isSaudi ? 'سعودي' : null,
        ]);

        self::registerEmployee($newEmployee);

        return $newEmployee;
    }

    /**
     * Split a name into its Arabic and Latin comparison forms.
     *
     * @return array{arabic: string, latin: string}
     */
    public static function normalize(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['arabic' => '', 'latin' => ''];
        }

        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $name) ?? $name;
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $name);
        $name = str_replace(['ى'], 'ي', $name);
        $name = str_replace(['ة'], 'ه', $name);

        preg_match_all('/[\x{0621}-\x{064A}]+/u', $name, $arabicMatches);
        preg_match_all('/[A-Za-z]+/', $name, $latinMatches);

        $arabic = strtolower(implode(' ', $arabicMatches[0]));
        $latin = strtolower(implode(' ', $latinMatches[0]));

        return [
            'arabic' => self::collapse($arabic),
            'latin' => self::collapse($latin),
        ];
    }

    protected static function collapse(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * @return array<int, string>
     */
    protected static function tokens(string $normalized): array
    {
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token): bool => $token !== '' && mb_strlen($token) > 1 && ! in_array($token, self::STOP_WORDS, true),
        ));
    }

    /**
     * @param  array<int, string>  $needleTokens
     */
    protected static function score(string $needle, array $needleTokens, string $candidate): int
    {
        if ($needle === '' || $candidate === '') {
            return 0;
        }

        $needleClean = str_replace(' ', '', $needle);
        $candidateClean = str_replace(' ', '', $candidate);

        // 1. Exact match when spaces are ignored (e.g. "انورحسين" vs "انور حسين" or "عبدالله" vs "عبد الله")
        if ($needleClean === $candidateClean && mb_strlen($needleClean) >= 4) {
            return 95;
        }

        $shortestClean = mb_strlen($needleClean) <= mb_strlen($candidateClean) ? $needleClean : $candidateClean;
        $longestClean = $shortestClean === $needleClean ? $candidateClean : $needleClean;

        // 2. Substring match without spaces (e.g. "انورحسين محمد" vs "انور حسين")
        if (mb_strlen($shortestClean) >= 5 && str_contains($longestClean, $shortestClean)) {
            return 85;
        }

        $shortest = mb_strlen($needle) <= mb_strlen($candidate) ? $needle : $candidate;
        $longest = $shortest === $needle ? $candidate : $needle;

        // 3. The whole stored name appears inside the statement name (or vice versa).
        if (mb_strlen($shortest) >= 6 && str_contains($longest, $shortest)) {
            return 80;
        }

        $candidateTokens = self::tokens($candidate);

        if (empty($needleTokens) || empty($candidateTokens)) {
            return 0;
        }

        $common = count(array_intersect($needleTokens, $candidateTokens));

        // 4. If tokens didn't match directly due to concatenated words in needle,
        // check if candidate tokens exist inside the concatenated needle
        if ($common < 2) {
            if (count($candidateTokens) >= 2) {
                $allFound = true;
                foreach ($candidateTokens as $cToken) {
                    if (! str_contains($needleClean, $cToken)) {
                        $allFound = false;
                        break;
                    }
                }
                if ($allFound) {
                    return 85;
                }
            }

            return 0;
        }

        $ratio = $common / count($needleTokens);

        return $ratio >= 0.6 ? 60 + (int) round(40 * $ratio) : 0;
    }

    /**
     * @return array<int, array{id: int, arabic: string, latin: string}>
     */
    protected static function employees(): array
    {
        if (self::$employees !== null) {
            return self::$employees;
        }

        self::$employees = [];

        Employee::query()
            ->with('user:id,name')
            ->select(['id', 'user_id'])
            ->chunk(500, function ($employees) {
                foreach ($employees as $employee) {
                    $name = $employee->user?->name;

                    if (empty($name)) {
                        continue;
                    }

                    $normalized = self::normalize($name);

                    if ($normalized['arabic'] === '' && $normalized['latin'] === '') {
                        continue;
                    }

                    self::$employees[] = [
                        'id' => $employee->id,
                        'arabic' => $normalized['arabic'],
                        'latin' => $normalized['latin'],
                    ];
                }
            });

        return self::$employees;
    }

    /**
     * Dynamically register a newly created employee into the cache so subsequent
     * records in the same batch/import run match this employee immediately.
     */
    public static function registerEmployee(Employee $employee): void
    {
        if (self::$employees === null) {
            return;
        }

        $name = $employee->user?->name;
        if (empty($name)) {
            return;
        }

        $normalized = self::normalize($name);
        if ($normalized['arabic'] === '' && $normalized['latin'] === '') {
            return;
        }

        self::$employees[] = [
            'id' => $employee->id,
            'arabic' => $normalized['arabic'],
            'latin' => $normalized['latin'],
        ];
    }

    /**
     * Clear the per-request cache (used by tests).
     */
    public static function reset(): void
    {
        self::$employees = null;
    }
}
