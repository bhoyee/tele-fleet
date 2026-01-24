@component('mail::message')
# Welcome to Tele-Fleet

Hello {{ $user?->name ?? 'there' }},

Your Tele-Fleet account has been set up successfully. You can now access the fleet management platform and begin managing trips, vehicles, and driver assignments.

@component('mail::panel')
**Login Details**

- **Email:** {{ $user?->email }}
- **Temporary Password:** {{ $plainPassword }}
@endcomponent

@component('mail::button', ['url' => $loginUrl])
Sign In to Tele-Fleet
@endcomponent

For security, please sign in and change your password immediately after your first login.

If you have any questions or need assistance, please contact your system administrator.

Thanks,  
The Tele-Fleet Team
@endcomponent
