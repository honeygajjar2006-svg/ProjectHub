#  ProjectHub | Project Management System

<p align="center">
  <strong>A centralized workspace for project tracking, deadlines, and team management.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-Backend-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-UI-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
  <img src="https://img.shields.io/badge/JavaScript-Interactive-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
</p>

<p align="center">
  <a href="#-overview">Overview</a> •
  <a href="#-features">Features</a> •
  <a href="#-technology-stack">Tech Stack</a> •
  <a href="#-architecture">Architecture</a> •
  <a href="#-setup-guide">Setup Guide</a> •
  <a href="#-project-team">Team</a>
</p>

---

## 🌿 Overview

**ProjectHub** is a web-based project management application developed as a **Web Technologies (WT) Open-Ended Project (OEP)** for the B.Tech Computer Engineering curriculum.

It provides a centralized interface to manage projects, organize team members, track priorities, monitor project statuses, and maintain deadlines.

Built with PHP and MySQL, the application demonstrates core full-stack web development concepts, including authentication, session management, database connectivity, CRUD operations, form validation, and interactive user interfaces.

## ✨ Features

| Feature | Description |
|:--|:--|
| 🔐 **Authentication** | User registration, login, logout, and session-based access. |
| 📊 **Dashboard** | Project statistics and a summary of recent projects. |
| 📁 **Project Management** | Create, view, update, and delete projects. |
| 🎯 **Priority Tracking** | Organize projects by Low, Medium, and High priority. |
| 📅 **Deadline Management** | Record due dates and validate date inputs. |
| 🔄 **Status Monitoring** | Track projects as Pending, In Progress, or Completed. |
| 👥 **Team Management** | Add team members to projects and view the team directory. |
| 🔎 **Table Search** | Filter visible table rows using JavaScript. |
| ✅ **Input Validation** | Validate submitted data on the client and server sides. |
| 🎨 **Interface Design** | Styled layouts, forms, tables, and Bootstrap components. |

## 🛠 Technology Stack

| Technology | Purpose |
|:--|:--|
| **PHP** | Backend logic, authentication, sessions, and database operations. |
| **MySQL** | Relational database for users, projects, and team members. |
| **MySQLi** | Database connectivity and prepared statements. |
| **HTML5** | Webpage structure, forms, tables, and navigation. |
| **CSS3** | Theme, layouts, styling, and responsive adjustments. |
| **Bootstrap** | Layout utilities and reusable interface components. |
| **JavaScript** | Table filtering, success alerts, and date validation. |
| **XAMPP** | Local Apache and MySQL development environment. |

## 🏗 Architecture

ProjectHub uses a simple client–server architecture.

```text
             USER / BROWSER
       HTML · CSS · Bootstrap
              JavaScript
                   |
                   | HTTP Requests
                   v
             PHP APPLICATION
                main.php
       Authentication and Sessions
          Validation and CRUD
                   |
                   | MySQLi Queries
                   v
             MYSQL DATABASE
             +-------------+
             |    users    |
             |   projects  |
             | team_members|
             +-------------+
```

### Application Workflow

1. The user registers or logs in through the browser.
2. PHP validates submitted information and manages the session.
3. MySQL stores, retrieves, or updates application data.
4. PHP processes the results and renders the requested page.
5. JavaScript enhances the interface with table filtering, alert handling, and date validation.

## 🗄 Database Design

The application uses the `projecthub_db` database with three main tables.

| Table | Description | Relationship |
|:--|:--|:--|
| `users` | Stores registered user details and password hashes. | Primary key: `id` |
| `projects` | Stores project names, descriptions, owners, priorities, due dates, and statuses. | `user_id` references `users.id` |
| `team_members` | Stores team member information associated with projects. | `project_id` references `projects.id` |

### Database Relationships

- One user can own multiple projects.
- Each project belongs to an owner.
- A project can have multiple team member records.
- Foreign keys help maintain referential integrity, with cascading deletion where configured in the schema.

## 📂 Project Structure

```text
ProjectHub/
│
├── main.php
├── script.js
├── style.css
├── projecthub.sql
├── README.md
└── images/
    └── Project image assets
```

| File / Folder | Purpose |
|:--|:--|
| `main.php` | Main application entry point, authentication, dashboard, project operations, and team management. |
| `script.js` | Table search, automatic success-message dismissal, and due-date validation. |
| `style.css` | Visual theme, layouts, forms, tables, and responsive styling. |
| `projecthub.sql` | Database and table definitions. |
| `README.md` | Project documentation and setup instructions. |
| `images/` | Images and other visual assets used by the project. |

## 🚀 Setup Guide

Follow these steps to run ProjectHub locally.

### Prerequisites

- [XAMPP](https://www.apachefriends.org/)
- A modern web browser
- The ProjectHub source files

### Step 1 — Configure the Project

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Copy the `ProjectHub` folder into your XAMPP `htdocs` directory.

Example Windows path:

```text
C:\xampp\htdocs\ProjectHub\
```

Use your actual XAMPP installation path if it differs.

### Step 2 — Import the Database

1. Open [phpMyAdmin](http://localhost/phpmyadmin/).
2. Select the **Import** tab.
3. Choose the `projecthub.sql` file.
4. Start the import and confirm that `projecthub_db` and its tables are created.

If Apache uses a custom port, open phpMyAdmin using the appropriate local URL.

### Step 3 — Check Database Configuration

Open `main.php` and ensure the connection settings match your local MySQL configuration.

| Setting | Default Local Value |
|:--|:--|
| Host | `localhost` |
| Username | `root` |
| Password | Blank, if configured that way in XAMPP |
| Database | `projecthub_db` |

Do not use a blank database password on a publicly accessible production server.

### Step 4 — Run the Application

Open the following URL in your browser:

**http://localhost/ProjectHub/main.php**

Register an account, log in, and explore the dashboard, project management, and team directory.

## 🔒 Security and Validation

ProjectHub demonstrates several fundamental security practices:

- **Password hashing:** Protects stored passwords from being saved as plaintext.
- **Prepared statements:** Helps reduce SQL injection risks.
- **Server-side validation:** Checks input before processing database operations.
- **Output escaping:** Helps reduce cross-site scripting risks when displaying untrusted text.
- **Session management:** Maintains the authenticated user's session.

These practices provide a useful foundation. Additional measures, such as CSRF protection and stronger session security, should be considered before production deployment.

## 🎓 Academic Context

| Detail | Information |
|:--|:--|
| Project | ProjectHub — Project Management System |
| Subject | Web Technologies (WT) |
| Project Type | Open-Ended Project (OEP) |
| Programme | B.Tech Computer Engineering |
| Team Size | 5 Members |

## 👥 Project Team

| No. | Team Member | Enrollment Number |
|:--:|:--|:--|

| 1 | Mariyah Tajani | ET24BTCO127 |
| 2 | Tasmi Hirpara | ET24BTCO130 |
| 3 | Arshi Trivedi | ET24BTCO142 |
| 4 | **Honey Gajjar** | **ET24BTCO150** | 
| 5 | Khushi Patel | ET24BTCO165 |

## 🔮 Future Enhancements

- Role-based authorization and granular access permissions.
- CSRF protection and improved session security.
- Enhanced project assignment and collaboration workflows.
- Automated testing and improved error handling.
- Deployment to a production-ready hosting environment.

*These are potential improvements, not claims about currently implemented functionality.*

## 📌 Project Status

**Academic OEP — Development Version**

This repository contains the application's source code and database schema. A compatible PHP server and MySQL database are required to run the application locally.

---

<p align="center">
  <strong>ProjectHub</strong><br>
  <em>Bringing projects, priorities, deadlines, and people into one workspace.</em>
</p>

<p align="center">
  B.Tech Computer Engineering · Web Technologies OEP
</p>
