<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Driver;
use App\Models\IncidentReport;
use App\Models\TripLog;
use App\Models\TripRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TripRequestCreated;
use App\Notifications\TripRequestApproved;
use App\Notifications\TripRequestAssigned;
use App\Notifications\TripRequestRejected;
use App\Notifications\TripRequestCancelled;
use App\Notifications\TripAssignmentPending;
use App\Notifications\TripCompletionReminderNotification;
use App\Notifications\OverdueTripNotification;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $hq = Branch::firstOrCreate(
            ['code' => 'HQ-001'],
            [
                'name' => 'Head Office',
                'address' => 'Central Business District',
                'city' => 'Lagos',
                'state' => 'Lagos',
                'phone' => '+234 800 000 0000',
                'email' => 'hq@tele-fleet.test',
                'is_head_office' => true,
            ]
        );

        $ikeja = Branch::firstOrCreate(
            ['code' => 'IKE-002'],
            [
                'name' => 'Ikeja Operations',
                'address' => 'Alausa Industrial Zone',
                'city' => 'Ikeja',
                'state' => 'Lagos',
                'phone' => '+234 800 000 0100',
                'email' => 'ikeja@tele-fleet.test',
            ]
        );

        $abuja = Branch::firstOrCreate(
            ['code' => 'ABJ-003'],
            [
                'name' => 'Abuja Logistics',
                'address' => 'Central Area',
                'city' => 'Abuja',
                'state' => 'FCT',
                'phone' => '+234 800 000 0200',
                'email' => 'abuja@tele-fleet.test',
            ]
        );

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@tele-fleet.test'],
            [
                'name' => 'Super Admin',
                'phone' => '+234 800 000 0001',
                'role' => User::ROLE_SUPER_ADMIN,
                'branch_id' => $hq->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $fleetManager = User::firstOrCreate(
            ['email' => 'fleet.manager@tele-fleet.test'],
            [
                'name' => 'Fleet Manager',
                'phone' => '+234 800 000 0002',
                'role' => User::ROLE_FLEET_MANAGER,
                'branch_id' => $hq->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $ikejaHead = User::firstOrCreate(
            ['email' => 'ikeja.head@tele-fleet.test'],
            [
                'name' => 'Ikeja Branch Head',
                'phone' => '+234 800 000 0101',
                'role' => User::ROLE_BRANCH_HEAD,
                'branch_id' => $ikeja->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $ikejaAdmin = User::firstOrCreate(
            ['email' => 'ikeja.admin@tele-fleet.test'],
            [
                'name' => 'Ikeja Branch Admin',
                'phone' => '+234 800 000 0102',
                'role' => User::ROLE_BRANCH_ADMIN,
                'branch_id' => $ikeja->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $abujaHead = User::firstOrCreate(
            ['email' => 'abuja.head@tele-fleet.test'],
            [
                'name' => 'Abuja Branch Head',
                'phone' => '+234 800 000 0201',
                'role' => User::ROLE_BRANCH_HEAD,
                'branch_id' => $abuja->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $abujaAdmin = User::firstOrCreate(
            ['email' => 'abuja.admin@tele-fleet.test'],
            [
                'name' => 'Abuja Branch Admin',
                'phone' => '+234 800 000 0202',
                'role' => User::ROLE_BRANCH_ADMIN,
                'branch_id' => $abuja->id,
                'status' => User::STATUS_ACTIVE,
                'password' => Hash::make('password'),
            ]
        );

        $vehicles = collect([
            Vehicle::firstOrCreate(
                ['registration_number' => 'TF-1001'],
                [
                    'branch_id' => $hq->id,
                    'make' => 'Toyota',
                    'model' => 'Hiace',
                    'year' => 2020,
                    'color' => 'Silver',
                    'fuel_type' => 'diesel',
                    'engine_capacity' => '2.8L',
                    'current_mileage' => 48210,
                    'last_maintenance_mileage' => 44000,
                    'insurance_expiry' => $now->copy()->addMonths(8)->toDateString(),
                    'registration_expiry' => $now->copy()->addMonths(10)->toDateString(),
                    'status' => 'available',
                    'maintenance_state' => 'ok',
                ]
            ),
            Vehicle::firstOrCreate(
                ['registration_number' => 'TF-2002'],
                [
                    'branch_id' => $ikeja->id,
                    'make' => 'Ford',
                    'model' => 'Ranger',
                    'year' => 2019,
                    'color' => 'Blue',
                    'fuel_type' => 'diesel',
                    'engine_capacity' => '2.2L',
                    'current_mileage' => 61500,
                    'last_maintenance_mileage' => 56000,
                    'insurance_expiry' => $now->copy()->addMonths(4)->toDateString(),
                    'registration_expiry' => $now->copy()->addMonths(5)->toDateString(),
                    'status' => 'maintenance',
                    'maintenance_state' => 'overdue',
                ]
            ),
            Vehicle::firstOrCreate(
                ['registration_number' => 'TF-3003'],
                [
                    'branch_id' => $abuja->id,
                    'make' => 'Hyundai',
                    'model' => 'Staria',
                    'year' => 2022,
                    'color' => 'Black',
                    'fuel_type' => 'petrol',
                    'engine_capacity' => '2.4L',
                    'current_mileage' => 18900,
                    'last_maintenance_mileage' => 15000,
                    'insurance_expiry' => $now->copy()->addMonths(11)->toDateString(),
                    'registration_expiry' => $now->copy()->addMonths(12)->toDateString(),
                    'status' => 'available',
                    'maintenance_state' => 'ok',
                ]
            ),
            Vehicle::firstOrCreate(
                ['registration_number' => 'TF-4004'],
                [
                    'branch_id' => $ikeja->id,
                    'make' => 'Kia',
                    'model' => 'Sorento',
                    'year' => 2021,
                    'color' => 'White',
                    'fuel_type' => 'petrol',
                    'engine_capacity' => '2.5L',
                    'current_mileage' => 27500,
                    'last_maintenance_mileage' => 23000,
                    'insurance_expiry' => $now->copy()->addMonths(9)->toDateString(),
                    'registration_expiry' => $now->copy()->addMonths(9)->toDateString(),
                    'status' => 'offline',
                    'maintenance_state' => 'ok',
                ]
            ),
        ]);

        $drivers = collect([
            Driver::firstOrCreate(
                ['email' => 'emeka.johnson@tele-fleet.test'],
                [
                    'full_name' => 'Emeka Johnson',
                    'license_number' => 'LIC-ENG-2041',
                    'license_type' => 'Class B',
                    'license_expiry' => $now->copy()->addMonths(10)->toDateString(),
                    'phone' => '+234 800 000 0300',
                    'address' => 'Ikeja',
                    'branch_id' => $ikeja->id,
                    'status' => 'active',
                ]
            ),
            Driver::firstOrCreate(
                ['email' => 'tomi.bassey@tele-fleet.test'],
                [
                    'full_name' => 'Tomi Bassey',
                    'license_number' => 'LIC-LAG-8830',
                    'license_type' => 'Class C',
                    'license_expiry' => $now->copy()->addMonths(4)->toDateString(),
                    'phone' => '+234 800 000 0400',
                    'address' => 'Victoria Island',
                    'branch_id' => $hq->id,
                    'status' => 'active',
                ]
            ),
            Driver::firstOrCreate(
                ['email' => 'ibrahim.musa@tele-fleet.test'],
                [
                    'full_name' => 'Ibrahim Musa',
                    'license_number' => 'LIC-ABJ-4502',
                    'license_type' => 'Class B',
                    'license_expiry' => $now->copy()->addYears(2)->toDateString(),
                    'phone' => '+234 800 000 0500',
                    'address' => 'Garki',
                    'branch_id' => $abuja->id,
                    'status' => 'active',
                ]
            ),
            Driver::firstOrCreate(
                ['email' => 'suspended.driver@tele-fleet.test'],
                [
                    'full_name' => 'Aisha Bello',
                    'license_number' => 'LIC-LAG-9901',
                    'license_type' => 'Class B',
                    'license_expiry' => $now->copy()->addMonths(18)->toDateString(),
                    'phone' => '+234 800 000 0600',
                    'address' => 'Ikoyi',
                    'branch_id' => $ikeja->id,
                    'status' => 'suspended',
                ]
            ),
        ]);

        $pendingTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0001'],
            [
                'branch_id' => $ikeja->id,
                'requested_by_user_id' => $ikejaAdmin->id,
                'purpose' => 'Client onboarding visit',
                'destination' => 'Victoria Island',
                'trip_date' => $now->copy()->subDay()->toDateString(),
                'trip_time' => '09:00',
                'estimated_distance_km' => 1,
                'number_of_passengers' => 2,
                'additional_notes' => 'Bring documents and ID cards.',
                'status' => 'pending',
            ]
        );

        $approvedTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0002'],
            [
                'branch_id' => $ikeja->id,
                'requested_by_user_id' => $ikejaHead->id,
                'purpose' => 'Branch inspection',
                'destination' => 'Alausa',
                'trip_date' => $now->copy()->addDays(2)->toDateString(),
                'trip_time' => '14:00',
                'estimated_distance_km' => 2,
                'number_of_passengers' => 1,
                'status' => 'approved',
                'approved_by_user_id' => $fleetManager->id,
                'approved_at' => $now->copy()->subHours(3),
            ]
        );

        $assignedTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0003'],
            [
                'branch_id' => $hq->id,
                'requested_by_user_id' => $superAdmin->id,
                'purpose' => 'Airport pickup',
                'destination' => 'Murtala Muhammed Airport',
                'trip_date' => $now->toDateString(),
                'trip_time' => '15:00',
                'estimated_distance_km' => 1.5,
                'number_of_passengers' => 3,
                'status' => 'assigned',
                'approved_by_user_id' => $fleetManager->id,
                'approved_at' => $now->copy()->subDay(),
                'assigned_vehicle_id' => $vehicles[0]->id,
                'assigned_driver_id' => $drivers[1]->id,
                'assigned_at' => $now->copy()->subHours(5),
            ]
        );

        $completedTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0004'],
            [
                'branch_id' => $abuja->id,
                'requested_by_user_id' => $abujaAdmin->id,
                'purpose' => 'Government meeting',
                'destination' => 'Central Area',
                'trip_date' => $now->copy()->subDays(5)->toDateString(),
                'trip_time' => '08:30',
                'estimated_distance_km' => 1,
                'number_of_passengers' => 2,
                'status' => 'completed',
                'approved_by_user_id' => $fleetManager->id,
                'approved_at' => $now->copy()->subDays(6),
                'assigned_vehicle_id' => $vehicles[2]->id,
                'assigned_driver_id' => $drivers[2]->id,
                'assigned_at' => $now->copy()->subDays(6),
                'is_completed' => true,
                'updated_by_user_id' => $fleetManager->id,
            ]
        );

        $rejectedTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0005'],
            [
                'branch_id' => $ikeja->id,
                'requested_by_user_id' => $ikejaAdmin->id,
                'purpose' => 'Late night drop-off',
                'destination' => 'Lekki',
                'trip_date' => $now->copy()->addDay()->toDateString(),
                'trip_time' => '20:00',
                'estimated_distance_km' => 1,
                'number_of_passengers' => 1,
                'status' => 'rejected',
                'rejection_reason' => 'Vehicle unavailable for requested time.',
                'updated_by_user_id' => $fleetManager->id,
            ]
        );

        $cancelledTrip = TripRequest::firstOrCreate(
            ['request_number' => 'TR-DEMO-0006'],
            [
                'branch_id' => $ikeja->id,
                'requested_by_user_id' => $ikejaHead->id,
                'purpose' => 'Team transfer',
                'destination' => 'Yaba',
                'trip_date' => $now->copy()->addDays(3)->toDateString(),
                'trip_time' => '11:30',
                'estimated_distance_km' => 2,
                'number_of_passengers' => 4,
                'status' => 'cancelled',
                'updated_by_user_id' => $ikejaHead->id,
            ]
        );

        TripLog::firstOrCreate(
            ['trip_request_id' => $completedTrip->id],
            [
                'start_mileage' => 18000,
                'end_mileage' => 18240,
                'distance_traveled' => 240,
                'fuel_before_trip' => 80,
                'fuel_after_trip' => 55,
                'fuel_consumed' => 25,
                'actual_start_time' => $now->copy()->subDays(5)->setTime(8, 30),
                'actual_end_time' => $now->copy()->subDays(5)->setTime(16, 0),
                'trip_duration_hours' => 7.5,
                'driver_name' => $drivers[2]->full_name,
                'driver_license_number' => $drivers[2]->license_number,
                'paper_logbook_ref_number' => 'LOG-0001',
                'driver_notes' => 'Smooth trip with light traffic.',
                'entered_by_user_id' => $abujaHead->id,
                'log_date' => $now->copy()->subDays(5)->toDateString(),
            ]
        );

        IncidentReport::firstOrCreate(
            ['reference' => 'INC-DEMO-001'],
            [
                'trip_request_id' => $assignedTrip->id,
                'branch_id' => $hq->id,
                'vehicle_id' => $vehicles[0]->id,
                'driver_id' => $drivers[1]->id,
                'reported_by_user_id' => $superAdmin->id,
                'title' => 'Minor scratch',
                'description' => 'Scratch on rear bumper during pickup.',
                'incident_date' => $now->copy()->subDay()->toDateString(),
                'incident_time' => '10:15',
                'location' => 'Airport parking',
                'severity' => IncidentReport::SEVERITY_MINOR,
                'status' => IncidentReport::STATUS_OPEN,
            ]
        );

        IncidentReport::firstOrCreate(
            ['reference' => 'INC-DEMO-002'],
            [
                'trip_request_id' => $completedTrip->id,
                'branch_id' => $abuja->id,
                'vehicle_id' => $vehicles[2]->id,
                'driver_id' => $drivers[2]->id,
                'reported_by_user_id' => $abujaHead->id,
                'title' => 'Flat tyre',
                'description' => 'Rear tyre punctured on return journey.',
                'incident_date' => $now->copy()->subDays(3)->toDateString(),
                'incident_time' => '14:45',
                'location' => 'Central Area',
                'severity' => IncidentReport::SEVERITY_MAJOR,
                'status' => IncidentReport::STATUS_REVIEW,
            ]
        );

        VehicleMaintenance::firstOrCreate(
            [
                'vehicle_id' => $vehicles[1]->id,
                'status' => VehicleMaintenance::STATUS_IN_PROGRESS,
            ],
            [
                'branch_id' => $ikeja->id,
                'created_by_user_id' => $fleetManager->id,
                'scheduled_for' => $now->copy()->subDays(2)->toDateString(),
                'started_at' => $now->copy()->subDay(),
                'description' => 'Routine service and brake check',
                'notes' => 'Awaiting spare parts',
                'cost' => 120000,
                'odometer' => 61500,
            ]
        );

        VehicleMaintenance::firstOrCreate(
            [
                'vehicle_id' => $vehicles[2]->id,
                'status' => VehicleMaintenance::STATUS_COMPLETED,
            ],
            [
                'branch_id' => $abuja->id,
                'created_by_user_id' => $fleetManager->id,
                'scheduled_for' => $now->copy()->subWeeks(2)->toDateString(),
                'started_at' => $now->copy()->subWeeks(2)->setTime(9, 0),
                'completed_at' => $now->copy()->subWeeks(2)->setTime(15, 30),
                'description' => 'Oil change and inspection',
                'notes' => 'All systems checked.',
                'cost' => 85000,
                'odometer' => 18000,
            ]
        );

        $supportConversation = ChatConversation::firstOrCreate(
            ['type' => ChatConversation::TYPE_SUPPORT, 'created_by_user_id' => $ikejaAdmin->id],
            [
                'issue_type' => ChatConversation::ISSUE_ADMIN,
                'status' => ChatConversation::STATUS_ACTIVE,
                'assigned_to_user_id' => $fleetManager->id,
            ]
        );

        ChatParticipant::firstOrCreate([
            'chat_conversation_id' => $supportConversation->id,
            'user_id' => $ikejaAdmin->id,
        ], [
            'accepted_at' => $now->copy()->subHours(2),
        ]);

        ChatParticipant::firstOrCreate([
            'chat_conversation_id' => $supportConversation->id,
            'user_id' => $fleetManager->id,
        ], [
            'accepted_at' => $now->copy()->subHours(2),
        ]);

        ChatMessage::firstOrCreate([
            'chat_conversation_id' => $supportConversation->id,
            'user_id' => $ikejaAdmin->id,
            'message' => 'Hello, we need an urgent vehicle for tomorrow.',
        ]);

        ChatMessage::firstOrCreate([
            'chat_conversation_id' => $supportConversation->id,
            'user_id' => $fleetManager->id,
            'message' => 'Received. Assigning a vehicle shortly.',
        ]);

        $closedConversation = ChatConversation::firstOrCreate(
            ['type' => ChatConversation::TYPE_SUPPORT, 'created_by_user_id' => $abujaAdmin->id],
            [
                'issue_type' => ChatConversation::ISSUE_TECH,
                'status' => ChatConversation::STATUS_CLOSED,
                'assigned_to_user_id' => $superAdmin->id,
                'closed_by_user_id' => $superAdmin->id,
                'closed_at' => $now->copy()->subDays(2),
            ]
        );

        ChatParticipant::firstOrCreate([
            'chat_conversation_id' => $closedConversation->id,
            'user_id' => $abujaAdmin->id,
        ], [
            'accepted_at' => $now->copy()->subDays(3),
        ]);

        ChatParticipant::firstOrCreate([
            'chat_conversation_id' => $closedConversation->id,
            'user_id' => $superAdmin->id,
        ], [
            'accepted_at' => $now->copy()->subDays(3),
        ]);

        ChatMessage::firstOrCreate([
            'chat_conversation_id' => $closedConversation->id,
            'user_id' => $abujaAdmin->id,
            'message' => 'System slowed down after report export.',
        ]);

        ChatMessage::firstOrCreate([
            'chat_conversation_id' => $closedConversation->id,
            'user_id' => $superAdmin->id,
            'message' => 'Issue resolved. Please retry now.',
        ]);

        Notification::send($fleetManager, new TripRequestCreated($pendingTrip));
        Notification::send($ikejaHead, new TripRequestApproved($approvedTrip));
        Notification::send($superAdmin, new TripRequestAssigned($assignedTrip));
        Notification::send($ikejaAdmin, new TripRequestRejected($rejectedTrip));
        Notification::send($ikejaHead, new TripRequestCancelled($cancelledTrip));
        Notification::send($fleetManager, new TripAssignmentPending($approvedTrip));
        Notification::send($superAdmin, new TripCompletionReminderNotification($pendingTrip));
        Notification::send($fleetManager, new OverdueTripNotification($pendingTrip));
    }
}
