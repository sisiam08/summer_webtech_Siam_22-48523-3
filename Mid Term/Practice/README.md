# Login System

A complete login system built with HTML, CSS, JavaScript, and PHP.

## Features

- **Frontend Validation**: Real-time form validation using JavaScript
- **Responsive Design**: Mobile-friendly CSS design
- **PHP Backend**: Secure server-side authentication
- **Session Management**: PHP sessions for user authentication
- **Password Security**: Password hashing for security
- **Remember Me**: Optional remember me functionality

## Files

1. **login.html** - Main login page with form
2. **login.css** - Styling for the login page
3. **login.js** - Client-side validation and AJAX handling
4. **login.php** - Server-side authentication logic
5. **dashboard.html** - Sample dashboard page after login

## Test Credentials

For testing purposes, you can use these credentials:

- **Admin**: username: `admin`, password: `admin123`
- **User**: username: `user`, password: `user123`
- **Demo**: username: `demo`, password: `demo123`

You can also use email addresses:
- `admin@example.com`
- `user@example.com`
- `demo@example.com`

## Setup Instructions

1. Place all files in your web server directory
2. Make sure PHP is enabled on your server
3. Open `login.html` in your browser
4. For database setup (optional), see comments in `login.php`

## Security Features

- Password hashing using PHP's `password_hash()`
- Input validation and sanitization
- CSRF protection ready (can be extended)
- Session management
- SQL injection prevention (when using database)

## Browser Compatibility

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- JavaScript required for enhanced validation

## Customization

- Modify the `$users` array in `login.php` to add more test users
- Update the redirect URL in `login.js` to point to your dashboard
- Customize the CSS in `login.css` for your brand colors
- Add more validation rules in `login.js` as needed
