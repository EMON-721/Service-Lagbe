# Service Lagbe

A web-based home service booking platform where customers can find and book trusted technicians, technicians can manage their jobs, and admins can manage the whole system.

## Developer

| | |
|---|---|
| **Name** | Md. Emon Hossain |
| **Student ID** | [223014123] |
| **Department** | Computer Science and Engineering (CSE) |
| **University** | University of Liberal Arts Bangladesh (ULAB) |

## Tech Stack

- **Backend:** PHP
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Local server:** XAMPP (Apache + MySQL)

## User Roles and Features

### Customer
- Sign up, log in and manage profile and address
- Browse services by category and view service details
- Book a service by choosing date, time slot, area size and service type
- Automatic price calculation
- Booking confirmation and booking history
- Leave a rating and review after a service is completed
- Contact form

### Technician
- Personal dashboard with assigned jobs
- Accept or reject job requests
- View customer details, address, schedule and notes for each job
- Track pending, confirmed and completed jobs

### Admin
- Dashboard with an overview of users, services, bookings and messages
- Manage users and roles
- Manage services and categories
- Manage bookings and update booking status
- Assign technicians to bookings
- Manage customer reviews
- Change the homepage hero image
- Read messages sent through the contact form

## Booking Flow

1. Customer selects a service and submits a booking
2. Admin assigns a technician
3. Technician accepts or rejects the job
4. Admin marks the job as completed
5. Customer leaves a review and the technician rating is updated automatically

## Setup (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\`
2. Copy `config.sample.php` to `config.php` and fill in your own settings
3. Start **Apache** and **MySQL** in the XAMPP Control Panel
4. Open `http://localhost/phpmyadmin`, click **Import**, choose `ServiceLagbe.sql` and click **Go**
5. Open `http://localhost/ServiceLagbe-main/`

## Author

Md. Emon Hossain, CSE, University of Liberal Arts Bangladesh (ULAB)