document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".signup-form");

    // Select all relevant fields by their name attribute
    const name = form.name;
    const employeeId = form.employee_id;
    const department = form.department;
    const position = form.position;
    const email = form.email;
    const contact = form.contact;
    const username = form.username;
    const password = form.password;
    const confirmPassword = form.confirm_password;

    let usernameAvailable = false;
    const checkUsernameUrl = "check_username.php"; // Shared endpoint for username check

    // === 1. Username Availability Check (on Blur/Focus Lost) ===
    username.addEventListener("blur", () => {
        const usernameValue = username.value.trim();
        if (usernameValue.length < 4) {
            // Only check if it meets minimum length
            return; 
        }

        fetch(checkUsernameUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `username=${encodeURIComponent(usernameValue)}`,
        })
        .then((response) => response.text())
        .then((data) => {
            // Note: The 'check_username.php' returns "taken" or "available"
            if (data.includes("taken")) {
                alert("That username is already taken. Please choose another one.");
                usernameAvailable = false;
            } else if (data.includes("error")) {
                 console.error("Error checking username:", data);
                 alert("An error occurred during username check. Please try again.");
                 usernameAvailable = false;
            } else {
                // Should be "available"
                usernameAvailable = true;
            }
        })
        .catch((error) => {
            console.error("Network Error:", error);
            alert("Could not connect to the server to check username availability.");
            usernameAvailable = false;
        });
    });

    // === 2. Form Submission Validation ===
    form.addEventListener("submit", (event) => {
        event.preventDefault();

        // 📝 A. Basic Field Validation
        if (name.value.trim() === "") return alert("Please enter your full name.");
        if (employeeId.value.trim() === "") return alert("Please enter your Employee ID.");
        if (department.value.trim() === "") return alert("Please select your department.");
        if (position.value.trim() === "") return alert("Please enter your position.");
        if (email.value.trim() === "") return alert("Please enter your email.");
        if (contact.value.trim() === "") return alert("Please enter your contact number.");
        if (username.value.trim().length < 4) return alert("Username must be at least 4 characters long.");

        // 🔑 B. Password Validation
        const passwordValue = password.value;
        const strongPassword = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

        if (!strongPassword.test(passwordValue)) {
            return alert("Password must be at least 8 characters long and include at least one letter, one number, and one special character (@$!%*?&).");
        }
        
        if (passwordValue !== confirmPassword.value) {
            return alert("Passwords do not match. Please check again.");
        }

        // 🛡️ C. Username Availability Check Status
        if (!usernameAvailable) {
            // Force the blur event check if user clicks submit without leaving username field
            username.dispatchEvent(new Event('blur')); 
            return alert("Please wait for the username check or choose an available username.");
        }
        
        // Final confirmation and submission
        if (confirm("Are you sure you want to register as a Coordinator?")) {
            form.submit();
        }
    });
});