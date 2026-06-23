🛡️ **CyberKavach Nexus**
Intelligent Event Management & Collaboration Platform for Student Clubs and Academic Institutions

📖 **Overview**
CyberKavach Nexus is a centralized event management and collaboration platform designed to simplify the planning, execution, and coordination of club activities and institutional events. The platform provides a structured workflow for faculty coordinators, student coordinators, and club members, enabling efficient communication, task management, participant registration, attendance tracking, and automated certificate distribution.

Unlike traditional event management systems that focus only on registrations, CyberKavach Nexus covers the complete event lifecycle—from event creation to post-event certification—while providing dedicated workspaces and role-based dashboards for every member involved.

🎯 **Problem Statement**
Managing college events often involves scattered communication, manual registrations, unorganized task assignments, attendance sheets, and certificate distribution through multiple platforms. This leads to:

Lack of coordination among club members.
Difficulty in monitoring task progress.
Manual handling of registrations and participant records.
Inefficient certificate generation and distribution.
No centralized platform for meetings and discussions.

CyberKavach Nexus solves these challenges by providing an integrated ecosystem where event planning, collaboration, registrations, attendance, and certifications are managed seamlessly from a single platform.

✨ **Key Features**
👥 *Role-Based Dashboard System*

Every club member is provided with a dedicated dashboard based on their role and responsibilities.

Supported roles include:

Faculty Coordinator
Student Coordinator
Technical Team
Design Team
Social Media Team
Registration Team
Volunteers and Other Club Members

Each dashboard provides personalized access to tasks, meetings, announcements, and event-related activities.

📅 *Smart Event Management*

Faculty Coordinators and Student Coordinators can create and manage events.

Approval Workflow
Faculty Coordinators can directly create events.
Events created by Student Coordinators require approval from Faculty Coordinators before becoming active.

This ensures proper supervision and authorization throughout the event planning process.

💬 *Automatic Event Workspaces*

One of the core features of CyberKavach Nexus is the automatic creation of dedicated workspaces for every event.

Whenever an event is approved:

✅ A unique workspace is generated automatically.

The workspace serves as a collaborative environment where coordinators and members can:

Discuss event requirements.
Share updates.
Coordinate responsibilities.
Monitor progress.
Maintain event-specific communication.

This eliminates the need for external communication platforms.

✅ Task Assignment and Review Workflow

Faculty Coordinators and Student Coordinators can assign tasks to club members and coordinators.

Tasks appear automatically on each member's My Tasks page.

Members can:

View assigned tasks.
Update their work progress.
Submit completed work.

Submitted work enters a review phase where it is verified by the Faculty Coordinator.

*Workflow*
Task Assignment
        ↓
My Tasks Page
        ↓
Work Submission
        ↓
Review by Coordinator
        ↓
Approval
        ↓
Completed Task

This creates accountability and transparency among team members.

*🔗 Automatic Registration Link Generation*
⭐ Core Feature

Whenever an event is created, CyberKavach Nexus automatically generates a registration link.
The system intelligently handles two event categories:

Individual Events
A dedicated registration form is created for single participants.

Team Events
A separate registration form is generated that allows team registration and member details.
Once shared, participants can register using the generated link without requiring manual form creation.

📝 *Participant Management*

All registrations are automatically stored and displayed inside the Participants module.

Coordinators can:

View participant information.
Track registrations.
Access enrollment details.
View team members for team-based events.
Maintain centralized participant records.

📋 *Event Attendance Management*

After an event is conducted, coordinators can mark participant attendance.
Attendance records support:

Present / Absent status.
Participant verification.
Event completion records.

These attendance records become the basis for certificate eligibility.

🏆 *Automated Certificate Generation and Email Delivery*
⭐ Core Feature

After event completion, certificates are generated automatically based on:

Attendance status.
Participant role.
Event category.
Achievement position.

Certificates can be generated for:

Participants
Winners
Runners-up
Coordinators
Automatic Email Distribution

CyberKavach Nexus also sends certificates directly to registered participants via email, eliminating manual certificate sharing.

This significantly reduces administrative effort and improves participant experience.

📅 *Club Meetings Management*
Faculty Coordinators can schedule club meetings directly from the platform.
Members receive notifications on their dashboards regarding upcoming meetings.
The meeting module supports:
.Meeting Attendance
Faculty can mark attendance of members present in the meeting.
.Discussion Notes
Important points discussed during the meeting can be recorded and preserved for future reference.
This provides complete documentation of club activities and decisions.

📢 *Announcement System*

Important updates and notices can be communicated to members through announcements.
Announcements may include:

Upcoming events.
Meeting reminders.
Task deadlines.
Important instructions.
📊 Team Progress Monitoring

CyberKavach Nexus enables coordinators to track progress through:

Task completion statistics.
Team-wise progress.
Pending assignments.
Overall event execution status.

🏗 *System Workflow*
Event Creation
      ↓
Approval (Faculty)
      ↓
Automatic Workspace Generation
      ↓
Automatic Registration Link Generation
      ↓
Participant Registration
      ↓
Task Assignment
      ↓
Discussion & Collaboration
      ↓
Event Execution
      ↓
Attendance Marking
      ↓
Certificate Generation
      ↓
Automatic Email Delivery

🛠 **Technology Stack**
*Frontend*
HTML5
CSS3
JavaScript

*Backend*
PHP 8

*Database*
MySQL

*Web Server*
Apache (XAMPP)

*Libraries and Tools*
Composer
PHPMailer (Email Services)
PDO Prepared Statements
Session Management
CSRF Protection

🏛 *Architecture*

CyberKavach Nexus follows a modular architecture based on the MVC design pattern.

Presentation Layer
        ↓
Views + Components
        ↓
Controllers / Middleware
        ↓
Business Logic
        ↓
Database Layer
        ↓
MySQL Database

🔐 *Security Features*

The platform implements several security mechanisms:

Password Hashing
Session Authentication
Role-Based Authorization
CSRF Protection
Input Sanitization
PDO Prepared Statements
Secure Database Access

📂 *Project Structure*
CyberKavach-Nexus
│
├── public/
├── src/
│   ├── Config/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   └── Helpers/
│
├── views/
│   ├── pages/
│   ├── layouts/
│   └── components/
│
├── vendor/
├── composer.json
└── README.md

🌟**Highlights**

✅ Role-Based Dashboard System
✅ Automatic Event Workspace Generation
✅ Task Assignment and Verification Workflow
✅ Automatic Registration Link Generation
✅ Individual and Team Event Registration Support
✅ Participant Management
✅ Attendance Tracking
✅ Automated Certificate Generation
✅ Automatic Email Delivery of Certificates
✅ Club Meeting Management with Discussion Notes
✅ Team Progress Monitoring
✅ Secure Authentication and Authorization

**🚀 Installation and Local Setup**
*1. Clone the Repository*
Run the following command in your terminal to download the project files:
code
Bash
git clone https://github.com/AnushkaShah02/CyberKavachNexus.git
cd CyberKavachNexus

*2. Install Dependencies*
Run Composer in the project directory to download the required packages (like PHPMailer):
code
Bash
composer install

*3. Database Setup*
Start Apache and MySQL in your local server control panel (such as XAMPP).
Open your web browser and navigate to http://localhost/phpmyadmin/.
Create a new database named cyber_kavach_db with the collation set to utf8mb4_unicode_ci.
Select the newly created database, click the Import tab at the top, select the schema.sql file from this project's root folder, and click Import (or Go).

*4. Configuration*
Open your database configuration file (typically located at src/Config/Database.php) and verify that your local parameters align with your MySQL setup:
code
PHP
// Database configuration parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'cyber_kavach_db'); 
define('DB_USER', 'root');            
define('DB_PASS', '');

5. Access the Platform
Navigate to the application in your browser:
Standard Cloned Setup: http://localhost/CyberKavachNexus/public/
Custom Folder Setup: http://localhost/cyber2/public/
🔑 Sandbox Demo Accounts
You can access the various role-based dashboards immediately using the pre-seeded accounts configured in the schema:
Faculty Coordinator: ramesh.faculty@cyberkavach.org (Password: Kavach@2026)
Student Coordinator: aarav.student@cyberkavach.org (Password: Kavach@2026)
Club Member: neha.member@cyberkavach.org (Password: Kavach@2026)

**👨‍💻 Contributors**
Anushka Shah 25CS097
Shrusti Chavada 25DCS016

**📜 License**
This project is licensed under the MIT License.

**⭐ CyberKavach Nexus**
**Transforming Club Operations Through Collaboration, Automation and Smart Event Management.**
