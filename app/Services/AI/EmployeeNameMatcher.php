<?php

namespace App\Services\AI;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\CompanyFormService;
use Illuminate\Database\Eloquent\Builder;
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

    /** @var array<int, array{id: int, company_id: ?int, arabic: string, latin: string}>|null */
    protected static ?array $employees = null;

    protected static ?string $cachedPasswordHash = null;

    protected static function getCachedPasswordHash(): string
    {
        return self::$cachedPasswordHash ??= Hash::make(Str::random(24));
    }

    /**
     * Splits a string that may contain mixed Arabic and Latin characters into separate parts.
     * Handles strings where Latin and Arabic words are directly attached without spaces,
     * e.g., "MOHAMMED SAHIDمحمد شاهد" => ['arabic' => 'محمد شاهد', 'latin' => 'MOHAMMED SAHID'].
     *
     * @return array{arabic: string, latin: string}
     */
    public static function splitBilingualName(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return ['arabic' => '', 'latin' => ''];
        }

        preg_match_all('/[\x{0621}-\x{064A}\s]+/u', $input, $arMatches);
        preg_match_all('/[A-Za-z\s]+/', $input, $enMatches);

        $ar = trim(preg_replace('/\s+/u', ' ', implode(' ', $arMatches[0] ?? [])));
        $en = trim(preg_replace('/\s+/u', ' ', implode(' ', $enMatches[0] ?? [])));

        return ['arabic' => $ar, 'latin' => $en];
    }

    /**
     * Smartly resolve an employee by any identifier (ID, employee number, national ID,
     * iqama number, Arabic name, English name, or concatenated bilingual name e.g. "MOHAMMED SAHIDمحمد شاهد").
     */
    public static function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $query = Employee::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // 1. Numeric matches (id, employee_number, national_id, iqama_number)
        if (is_numeric($identifier)) {
            // All branches inside one grouped closure so the company scope
            // applies to every condition (an OR outside the group would
            // ignore company_id entirely).
            $emp = (clone $query)->where(function ($q) use ($identifier) {
                $q->where(fn ($w) => $w->where('id', $identifier)->orWhere('employee_number', $identifier))
                    ->orWhereHas('detail', fn ($d) => $d->where('national_id', $identifier)->orWhere('iqama_number', $identifier));
            })->first();
            if ($emp) {
                return $emp;
            }
        }

        // 2. Direct exact match on employee_number, national_id, iqama_number, or phone
        $emp = (clone $query)->where(function ($q) use ($identifier) {
            $q->where('employee_number', $identifier)
                ->orWhereHas('detail', fn ($d) => $d->where('national_id', $identifier)->orWhere('iqama_number', $identifier))
                ->orWhereHas('user', fn ($u) => $u->where('phone', $identifier));
        })->first();
        if ($emp) {
            return $emp;
        }

        // 3. Direct exact match on User name or metadata->name_en
        //    (exclusive match only — a duplicated name must not be trusted)
        $emp = self::singleMatch((clone $query)->where(function ($q) use ($identifier) {
            $q->whereHas('user', fn ($uq) => $uq->where('name', $identifier))
                ->orWhere('metadata->name_en', $identifier);
        }));
        if ($emp) {
            return $emp;
        }

        // 4. Split bilingual strings
        $bilingual = self::splitBilingualName($identifier);
        $ar = $bilingual['arabic'];
        $en = $bilingual['latin'];

        // 4.a Exact match on the Arabic part (exclusive only)
        if ($ar !== '') {
            $emp = self::singleMatch((clone $query)->whereHas('user', fn ($uq) => $uq->where('name', $ar)));
            if ($emp) {
                return $emp;
            }
        }

        // 4.b Exact/substring/permutation match on the Latin part (exclusive only)
        if ($en !== '') {
            $emp = self::singleMatch((clone $query)->where('metadata->name_en', $en));
            if ($emp) {
                return $emp;
            }

            if (strlen($en) >= 4) {
                $emp = self::uniqueLatinSubstring((clone $query), $en);
                if ($emp) {
                    return $emp;
                }
            }

            // Token permutation matching (e.g. "MOHAMMED SAHID" vs "SAHID MOHAMMED")
            $enTokens = array_filter(explode(' ', $en), fn ($t) => strlen($t) > 2);
            if (count($enTokens) >= 2) {
                $tokenQuery = (clone $query);
                foreach ($enTokens as $token) {
                    $tokenQuery->where('metadata->name_en', 'like', "%{$token}%");
                }
                $emp = self::singleMatch($tokenQuery);
                if ($emp) {
                    // Ensure the candidate doesn't have an extra completely distinct name (e.g. BABUL)
                    $candEn = (string) ($emp->metadata['name_en'] ?? '');
                    $candTokens = array_filter(explode(' ', strtoupper($candEn)), fn ($t) => strlen($t) > 2);
                    $diff = array_diff($candTokens, array_map('strtoupper', $enTokens));
                    $diff = array_filter($diff, fn ($t) => ! in_array($t, ['MD', 'MR', 'MOHD', 'MOHAMMAD', 'MOHAMMED', 'BIN', 'IBN', 'AL', 'EL'], true));
                    if (empty($diff)) {
                        return $emp;
                    }
                }
            }
        }

        // 5. Normalized space-agnostic equality against the cached employee list
        //    (single pass over a per-process cache instead of a full-table query
        //     for every row). Exclusive match only.
        $needleArabic = self::compactArabic($ar !== '' ? $ar : $identifier);
        $needleLatin = self::compactLatin($en !== '' ? $en : $identifier);

        if ($needleArabic !== '' || $needleLatin !== '') {
            $candidateIds = [];

            foreach (self::employees() as $cached) {
                if ($companyId !== null && ($cached['company_id'] ?? null) !== $companyId) {
                    continue;
                }

                if ($needleArabic !== '' && $cached['arabic'] !== '' && self::compactArabic($cached['arabic']) === $needleArabic) {
                    $candidateIds[] = $cached['id'];
                }

                if ($needleLatin !== '' && $cached['latin'] !== '' && self::compactLatin($cached['latin']) === $needleLatin) {
                    $candidateIds[] = $cached['id'];
                }
            }

            $candidateIds = array_values(array_unique($candidateIds));

            if (count($candidateIds) === 1) {
                return Employee::find($candidateIds[0]);
            }
        }

        // 6. Fallback to fuzzy matcher
        return self::find($identifier, $companyId);
    }

    public static function find(string $identifier, ?int $companyId = null): ?Employee
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $query = Employee::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // 1. Numeric match (id, employee number, national id, iqama number)
        if (is_numeric($identifier)) {
            // Grouped together so the company scope covers every branch.
            $employee = (clone $query)->where(function ($q) use ($identifier) {
                $q->where(fn ($w) => $w->where('id', $identifier)->orWhere('employee_number', $identifier))
                    ->orWhereHas('detail', fn ($d) => $d->where('national_id', $identifier)->orWhere('iqama_number', $identifier));
            })->first();

            if ($employee) {
                return $employee;
            }
        }

        // 2. Direct exact match on User name or English name in metadata
        $direct = (clone $query)->where(function ($q) use ($identifier) {
            $q->whereHas('user', fn ($uq) => $uq->where('name', $identifier))
                ->orWhere('metadata->name_en', $identifier);
        })->first();
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
            if ($companyId !== null && isset($employee['company_id']) && $employee['company_id'] !== $companyId) {
                continue;
            }

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
    public static function findOrCreate(
        string $identifier,
        ?int $companyId = null,
        ?string $companyName = null,
        ?string $explicitNameAr = null,
        ?string $explicitNameEn = null
    ): Employee {
        $employee = self::resolveEmployee($identifier, $companyId);

        if ($employee) {
            return $employee;
        }

        // If companyName provided, resolve or create company; otherwise leave companyId as-is (null if not specified)
        // so that employees without an establishment in the file are created without one (company_id = null).
        if (! empty($companyName)) {
            $companyRes = CompanyFormService::resolveOrCreateCompanyByName($companyName, $companyId);
            if (! empty($companyRes['company_id'])) {
                $companyId = (int) $companyRes['company_id'];
            }
        }

        // Smart detection of user type & ID from identifier
        $cleanId = preg_replace('/\D/', '', $identifier);
        $idParam = (strlen($cleanId) === 10) ? $cleanId : null;
        $userType = UserType::detect(nationalId: $idParam, name: $identifier);
        $isSaudi = ($userType === UserType::SAUDI_EMPLOYEE);

        $bilingual = self::splitBilingualName($identifier);
        $nameAr = ! empty($explicitNameAr) ? trim($explicitNameAr) : ($bilingual['arabic'] !== '' ? $bilingual['arabic'] : trim($identifier));
        $nameEn = ! empty($explicitNameEn) ? trim($explicitNameEn) : ($bilingual['latin'] !== '' ? $bilingual['latin'] : null);

        $userEmail = 'emp_'.Str::random(8).'@company.local';
        $user = User::create([
            'name' => $nameAr,
            'email' => $userEmail,
            'phone' => null,
            'password' => self::getCachedPasswordHash(),
            'user_type' => $userType,
            'status' => 'active',
        ]);

        $employeeMetadata = [];
        if (! empty($nameEn)) {
            $employeeMetadata['name_en'] = $nameEn;
        }

        $newEmployee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'basic_salary' => 0.0,
            'status' => GeneralStatus::ACTIVE,
            'metadata' => ! empty($employeeMetadata) ? $employeeMetadata : null,
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
     * Accept a name-based match only when exactly one employee qualifies.
     * A name that could belong to several employees must never silently
     * attach money (installments, payments, payroll) to an arbitrary pick.
     */
    protected static function singleMatch(Builder $query): ?Employee
    {
        $results = $query->limit(25)->get();
        $ids = $results->pluck('id')->unique();

        return $ids->count() === 1 ? $results->first() : null;
    }

    /**
     * Latin-substring match with the same negligible-extra guard used by the
     * fuzzy scorer: the stored name may differ from the statement name by at
     * most two trailing/extra characters ("MD SOHAIL" vs "MD SOHAIL MIAH" is a
     * different, fuller name, so no merge happens).
     *
     * @param  Builder<Employee>  $query
     */
    protected static function uniqueLatinSubstring(Builder $query, string $en): ?Employee
    {
        $needle = strtolower(preg_replace('/[^A-Za-z]/', '', $en) ?? '');

        if ($needle === '') {
            return null;
        }

        $results = $query->where('metadata->name_en', 'like', "%{$en}%")->limit(25)->get();

        $matches = $results->filter(function (Employee $employee) use ($needle) {
            $stored = strtolower(preg_replace('/[^A-Za-z]/', '', (string) ($employee->metadata['name_en'] ?? '')));

            return str_contains($stored, $needle) && mb_strlen($stored) - mb_strlen($needle) <= 2;
        });

        $ids = $matches->pluck('id')->unique();

        return $ids->count() === 1 ? $matches->first() : null;
    }

    /**
     * Arabic-only characters (spaces included), lowercased and diacritic-free,
     * for space-agnostic equality checks.
     */
    protected static function compactArabic(string $value): string
    {
        $compact = preg_replace('/[^\p{Arabic}]/u', '', self::normalize($value)['arabic'] ?? '') ?? '';

        return mb_strtolower(str_replace(' ', '', $compact));
    }

    /**
     * Latin-only characters, lowercased, for space-agnostic equality checks.
     */
    protected static function compactLatin(string $value): string
    {
        $compact = preg_replace('/[^A-Za-z]/', '', strtolower(self::normalize($value)['latin'] ?? ''));

        return strtolower($compact);
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

        // 2. Substring match without spaces (spelling variants like ا/أ/ة or a
        //    missing preposition), only when the extra characters are negligible.
        //    A real subset name (e.g. "أحمد محمد" vs "أحمد محمد حسين مياه") is a
        //    DIFFERENT person: the import must create a new employee, never merge.
        if (mb_strlen($shortestClean) >= 5 && str_contains($longestClean, $shortestClean)) {
            $extra = mb_strlen($longestClean) - mb_strlen($shortestClean);

            return $extra <= 2 ? 85 : 0;
        }

        $shortest = mb_strlen($needle) <= mb_strlen($candidate) ? $needle : $candidate;
        $longest = $shortest === $needle ? $candidate : $needle;

        // 3. The whole stored name appears inside the statement name (or vice versa)
        //    with the same negligible-extra guard, so partial names are not merged.
        if (mb_strlen($shortest) >= 6 && str_contains($longest, $shortest)) {
            $extra = mb_strlen($longest) - mb_strlen($shortest);

            return $extra <= 2 ? 80 : 0;
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

        $totalUniqueTokens = count(array_unique(array_merge($needleTokens, $candidateTokens)));
        $ratio = $common / max(1, $totalUniqueTokens);

        // If candidate has distinct tokens not in needle (or vice versa), penalize to avoid false merges
        $unmatched = count(array_diff($candidateTokens, $needleTokens)) + count(array_diff($needleTokens, $candidateTokens));
        if ($unmatched > 0) {
            $ratio -= 0.15 * $unmatched;
        }

        return $ratio >= 0.7 ? 60 + (int) round(40 * max(0.0, $ratio)) : 0;
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
            ->select(['id', 'company_id', 'user_id', 'metadata'])
            ->chunk(500, function ($employees) {
                foreach ($employees as $employee) {
                    $name = $employee->user?->name;
                    $nameEn = $employee->metadata['name_en'] ?? null;
                    $combined = trim(($name ?? '').' '.($nameEn ?? ''));

                    if (empty($combined)) {
                        continue;
                    }

                    $normalized = self::normalize($combined);

                    if ($normalized['arabic'] === '' && $normalized['latin'] === '') {
                        continue;
                    }

                    self::$employees[] = [
                        'id' => $employee->id,
                        'company_id' => $employee->company_id,
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
        $nameEn = $employee->metadata['name_en'] ?? null;
        $combined = trim(($name ?? '').' '.($nameEn ?? ''));

        if (empty($combined)) {
            return;
        }

        $normalized = self::normalize($combined);
        if ($normalized['arabic'] === '' && $normalized['latin'] === '') {
            return;
        }

        self::$employees[] = [
            'id' => $employee->id,
            'company_id' => $employee->company_id,
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
