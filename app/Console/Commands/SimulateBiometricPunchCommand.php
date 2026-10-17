<?php

namespace App\Console\Commands;

use App\Models\AttendanceDevice;
use App\Models\Company;
use App\Models\Employee;
use App\Services\BiometricAttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulateBiometricPunchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zk:simulate 
                            {--employee= : Employee ID or Employee Number}
                            {--type=check_in : Punch type (check_in, check_out, random_day)}
                            {--sn=ZK-SIM-001 : Device Serial Number}
                            {--time= : Custom punch time (Y-m-d H:i:s)}
                            {--all : Simulate for all active employees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate ZKTeco F34 Lite / ADMS biometric punch transactions to test attendance pipeline';

    /**
     * Execute the console command.
     */
    public function handle(BiometricAttendanceService $service): int
    {
        $sn = $this->option('sn');
        $type = $this->option('type');
        $employeeOpt = $this->option('employee');
        $customTime = $this->option('time');
        $isAll = $this->option('all');

        $this->info("🔄 Initializing ZKTeco Biometric Simulator (SN: {$sn})...");

        // 1. Ensure test device exists
        $company = Company::first();
        $device = AttendanceDevice::firstOrCreate(
            ['code' => $sn],
            [
                'company_id' => $company?->id ?? 1,
                'name' => 'جهاز بصمة افتراضي (F34 Lite Simulator)',
                'serial_number' => $sn,
                'model' => 'ZKTeco F34 Lite',
                'device_type' => 'fingerprint_face',
                'ip_address' => '192.168.1.200',
                'location' => 'المدخل الرئيسي',
                'status' => 'active',
                'last_seen_at' => now(),
            ]
        );

        // 2. Perform simulated Handshake
        $handshakeResponse = $service->handleHandshake($sn, ['pushver' => '2.4.0']);
        $this->line("📡 Handshake Response:\n<comment>{$handshakeResponse}</comment>");

        // 3. Collect employees to simulate
        $employees = collect();
        if ($isAll) {
            $employees = Employee::with('user')->where('status', 'active')->get();
        } elseif ($employeeOpt) {
            $emp = Employee::with('user')->where('id', $employeeOpt)
                ->orWhere('employee_number', $employeeOpt)
                ->first();
            if ($emp) {
                $employees->push($emp);
            } else {
                $this->error("❌ Employee [{$employeeOpt}] not found!");
                return self::FAILURE;
            }
        } else {
            $emp = Employee::with('user')->first();
            if ($emp) {
                $employees->push($emp);
            } else {
                $this->error("❌ No employees found in system to simulate!");
                return self::FAILURE;
            }
        }

        $this->info("👥 Simulating punches for " . $employees->count() . " employee(s)...");

        $rawLines = [];
        $today = Carbon::today();

        foreach ($employees as $employee) {
            $pin = $employee->employee_number ?: (string)$employee->id;
            $name = $employee->user?->name ?? "موظف #{$employee->id}";

            if ($type === 'random_day') {
                // Simulate Check-In (between 07:50 and 08:35)
                $inMinute = rand(-10, 35);
                $inTime = (clone $today)->setTime(8, 0, 0)->addMinutes($inMinute);
                $rawLines[] = "{$pin}\t{$inTime->format('Y-m-d H:i:s')}\t0\t1\t0\t0";

                // Simulate Check-Out (between 16:00 and 17:30)
                $outMinute = rand(0, 90);
                $outTime = (clone $today)->setTime(16, 0, 0)->addMinutes($outMinute);
                $rawLines[] = "{$pin}\t{$outTime->format('Y-m-d H:i:s')}\t1\t1\t0\t0";

                $this->info("   👤 {$name} (PIN: {$pin}) -> In: {$inTime->format('H:i')}, Out: {$outTime->format('H:i')}");
            } else {
                $rawStatus = ($type === 'check_out') ? '1' : '0';
                $punchTime = $customTime ? Carbon::parse($customTime) : now();
                $rawLines[] = "{$pin}\t{$punchTime->format('Y-m-d H:i:s')}\t{$rawStatus}\t1\t0\t0";

                $this->info("   👤 {$name} (PIN: {$pin}) -> {$type} at {$punchTime->format('Y-m-d H:i:s')}");
            }
        }

        // 4. Send the simulated ATTLOG payload
        $rawPayload = implode("\n", $rawLines);
        $processedCount = $service->processAttLog($sn, $rawPayload);

        $this->newLine();
        $this->info("✅ Simulation Complete! Processed {$processedCount} punch records.");
        $this->line("👉 Check Attendance Records in Filament: /attendances");

        return self::SUCCESS;
    }
}
