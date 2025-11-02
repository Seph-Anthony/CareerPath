document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".signup-form");

  const firstName = document.querySelector('input[name="first_name"]');
  const lastName = document.querySelector('input[name="last_name"]');
  const course = document.querySelector('select[name="course"]'); 
  const yearLevel = document.querySelector('select[name="year_level"]');
  const description = document.querySelector('textarea[name="description"]');
  
  // 🌟 NEW FIELDS 🌟
  const email = document.querySelector('input[name="email"]'); 
  const contact = document.querySelector('input[name="contact"]');
  // 🌟 END NEW FIELDS 🌟

  const username = document.querySelector('input[name="username"]');
  const password = document.querySelector('input[name="password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');


  let usernameAvailable = false;

  // === Check Username Availability (AJAX) ===
  username.addEventListener("blur", () => {
    const usernameValue = username.value.trim();
    if (usernameValue === "") return;

    fetch("check_username.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `username=${encodeURIComponent(usernameValue)}`,
    })
      .then((response) => response.text())
      .then((data) => {
        if (data === "taken") {
          alert("This username is already taken. Please choose another one.");
          usernameAvailable = false;
        } else {
          usernameAvailable = true;
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();

    // === Basic validations ===
    if (firstName.value.trim() === "") return alert("Please enter your first name.");
    if (lastName.value.trim() === "") return alert("Please enter your last name.");
    if (course.value.trim() === "") return alert("Please enter your course.");
    if (yearLevel.value.trim() === "") return alert("Please select your year level.");
    
    // 🌟 NEW VALIDATION CHECKS 🌟
    if (email.value.trim() === "") return alert("Please enter your email address.");
    if (contact.value.trim() === "") return alert("Please enter your contact number.");
    // 🌟 END NEW VALIDATION CHECKS 🌟

    if (username.value.trim().length < 4) return alert("Username must be at least 4 characters.");
    if (!usernameAvailable) return alert("Username is not available. Please choose another.");
    
    const passwordValue = password.value;
    // Password must be at least 8 chars, 1 letter, 1 number, 1 special char
    const strongPassword = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    
    if (!strongPassword.test(passwordValue))
      return alert("Password must be at least 8 characters long and include at least one letter, one number, and one special character (@$!%*?&).");
    
    if (password.value !== confirmPassword.value)
      return alert("Passwords do not match. Please check again.");

    if (!confirm("Are you sure you want to register as a Student?")) return;

    // Submit the form if all checks pass
    form.submit();
  });
});