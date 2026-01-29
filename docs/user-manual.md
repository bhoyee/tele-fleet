# Tele-Fleet User Manual

## Document Control
- Document: Tele-Fleet User Manual
- Audience: End users and operations staff
- Version: v1.0.0
- Prepared for: Tele-Fleet project
- Prepared by: Operations

## Table of Contents
1. Introduction
2. Roles and Access
3. Logging In
4. Dashboard Overview
5. Trip Requests
6. Trip Assignment (Fleet Manager)
7. Logbooks
8. Incidents
9. Vehicles
10. Drivers
11. Maintenance
12. Reports
13. Notifications
14. System Tools (Super Admin)
15. Chat Support
16. Troubleshooting

## 1. Introduction
Tele-Fleet manages trips, vehicles, drivers, incidents, maintenance, and reporting in a single system. This manual explains the workflow by role.

## 2. Roles and Access
- Super Admin: Full access, user management, configuration, restore/force delete.
- Fleet Manager: Operational control, assignments, maintenance, and reporting.
- Branch Head: Branch oversight, trip approvals, branch reports.
- Branch Admin: Submits trip requests and incident reports for own branch.

## 3. Logging In
1. Open the login page.
2. Enter your credentials.
3. After login, you are redirected to your dashboard.

## 4. Dashboard Overview
Each role has a tailored dashboard. Key elements:
- Status cards (available vehicles, pending approvals, incidents).
- Calendar and charts (where enabled).
- Upcoming trips table.
- Live updates via real-time or silent polling fallback.

## 5. Trip Requests
### Create a trip
- Navigate to Trips > New Trip.
- Provide purpose, destination, trip date/time, passengers, and estimated trip days.
- Submit the request.

### Track your request
- Use Trips list to see status: pending, approved, assigned, completed, rejected.
- You can cancel pending trips if the trip date/time has not passed.

## 6. Trip Assignment (Fleet Manager)
- Open a pending request.
- Approve or reject with a reason.
- Assign an available vehicle and driver.
- Approved trips without assignment trigger reminders near the trip time.

## 7. Logbooks
- After trip completion, fleet roles enter logbook details.
- Logbook due/overdue status is shown in the logbook table.

## 8. Incidents
### Create incident report
- Incidents > New Incident.
- Select a trip (driver/vehicle auto-filled).
- Add description, severity, date/time, and attachments.

### Status workflow
- Open, Under Review, Resolved, Cancelled.
- Notifications are sent when status changes.

## 9. Vehicles
- Fleet roles manage vehicle records.
- Vehicle status: Available, In Use, Maintenance, Offline.
- Maintenance state: OK, Due, Overdue.

## 10. Drivers
- Fleet roles manage driver records.
- Driver status: Active, Inactive, Suspended.
- Suspended or inactive drivers cannot be assigned to trips.

## 11. Maintenance
- Schedule maintenance with details and costs.
- When maintenance is in progress, vehicles are marked as unavailable.
- Completion resets mileage targets.

## 12. Reports
### My Reports
- Personal trip report for branch users.

### Branch Report
- Branch-wide report for Branch Head.

### Fleet Reports
- Multi-branch operational view for Fleet Manager and Super Admin.
- Includes branch leaderboards for trips, driver usage, and incidents.

### Custom Reports
- Choose Trips/Vehicles/Drivers/Incidents/Maintenance.
- Filter by branch and date range.
- Export CSV or PDF.

## 13. Notifications
- In-app bell icon shows unread notifications.
- Email is sent for key events (approvals, rejections, incidents, reminders).

## 14. System Tools (Super Admin)
### Database Backups
- Open **Database Backups** from the sidebar.
- Click **Run Backup** to generate a new file.
- Download or delete backups from the list.

### System Logs
- Open **System Logs** from the sidebar.
- Select a log file to view recent entries.
- Download logs for audit or troubleshooting.

## 15. Chat Support
- Branch roles can request support via the chat widget.
- Fleet Manager/Super Admin can accept and respond in real time.

## 16. Troubleshooting
- If the app feels delayed, wait a few seconds before clicking again.
- If you see a permission error, confirm your role.
- For login issues, contact the system administrator.
