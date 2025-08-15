# Rumah Adat Admin

## Project Overview
The Rumah Adat Admin project is designed to manage and display information about traditional houses in Indonesia. It includes a dashboard for users to view details about various traditional houses and an admin panel for managing user data.

## File Structure
```
rumah-adat-admin
├── src
│   ├── dashboard.php        # Main dashboard displaying traditional houses information
│   ├── admin.php            # Admin panel for displaying user data
│   └── db
│       └── connect.php      # Database connection handling
├── sql
│   └── schema.sql           # SQL schema for database setup
├── README.md                # Project documentation
```

## Setup Instructions

1. **Database Setup**
   - Create a MySQL database for the project.
   - Run the SQL commands in `sql/schema.sql` to set up the necessary tables.

2. **Database Connection**
   - Update the `src/db/connect.php` file with your database credentials (host, username, password, and database name).

3. **Running the Application**
   - Place the `rumah-adat-admin` folder in your web server's root directory (e.g., `htdocs` for XAMPP).
   - Access the dashboard by navigating to `http://localhost/rumah-adat-admin/src/dashboard.php` in your web browser.
   - Access the admin panel by navigating to `http://localhost/rumah-adat-admin/src/admin.php`.

## Usage
- The dashboard allows users to view information about traditional houses.
- The admin panel displays user data, including names, school origins, and correct scores.

## Contributing
Contributions are welcome! Please feel free to submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is open-source and available under the MIT License.