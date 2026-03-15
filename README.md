# Carpooling & Sustainable Transport System 🚗🌱

## 📌 Project Overview
This project was developed as a core assignment for the **Responsive Web Design & Development (RWDD)** module during **Semester 4** at **Asia Pacific University (APU)**. The system is designed to encourage sustainable commuting within the campus community through carpooling, event coordination, and a reward-based incentive system.

The primary focus of this repository is on **Backend Functionality**, **Database Management**, and **Core Business Logic** implementation.

## 🛠 Tech Stack
* **Backend:** PHP (Server-side logic)
* **Database:** MySQL
* **Environment:** XAMPP / WampServer
* **Frontend:** HTML5, CSS3, JavaScript
* **Version Control:** Git / GitHub

## 👤 My Key Responsibilities (Backend & Functionality)
In this group project, I was responsible for architecting the logic and data flow for several critical modules to ensure a seamless user experience and robust data handling.

### 1. User Authentication & Session Management
* **Comprehensive Login & Sign-up:** Developed the backend logic for secure user registration and multi-role authentication (User, Driver, Event Organizer).
* **Session Handling:** Implemented session-based security for login persistence and logout procedures.
* **Server-side Validation:** Coded input validation to ensure data integrity during account creation.

### 2. Registration Workflows
* **Driver Registration:** Built the functional module for driver applications, including vehicle capacity logic and image/document path management for IC and License uploads.
* **Event Organizer Registration:** Implemented a "Pending/Approved" status workflow for organizers, ensuring only verified users can host events.

### 3. Core Event & Reward System Logic
* **Event Management:** Developed the **Create Event** and **Event Detail** pages, handling complex form submissions, date/time logic, and image upload processing.
* **Reward Engine:** Coded the backend logic for the **Reward Page**, managing point deductions and record updates in the database when a user claims an incentive.
* **Feedback System:** Implemented the functional logic for the **Feedback Page** to capture and store user ratings and comments.

### 4. Database Schema Design
* Collaborated on the **Entity Relationship Diagram (ERD)** and was responsible for creating and linking several core tables to maintain relational integrity.

## 📂 Project Structure (Relevant Modules)
* `/Database`: Contains the `.sql` schema for system initialization.
* `login.php / signup.php`: Core authentication logic.
* `create_event.php / event_detail.php`: Event management engine.
* `register_driver.php / register_event_organizer.php`: Specialized registration workflows.

## ⚠️ Known Issues & Limitations
This project is a **functional prototype**. As such:
* **Logic Refinement:** There are known bugs in certain edge-case scenarios (e.g., handling simultaneous data requests) that are currently under review.
* **UI Focus:** The current version prioritizes backend functionality over frontend aesthetics; UI refinements are ongoing.

---
*Disclaimer: This is an academic project developed for educational purposes.*
