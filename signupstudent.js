document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".signup-form");

  const firstName = document.querySelector('input[name="first_name"]');
  const lastName = document.querySelector('input[name="last_name"]');
  // 🐛 FIX THIS LINE: Change 'input' to 'select'
  const course = document.querySelector('select[name="course"]'); 
  const yearLevel = document.querySelector('select[name="year_level"]');
  const description = document.querySelector('textarea[name="description"]');
  const username = document.querySelector('input[name="username"]');
  const password = document.querySelector('input[name="password"]');
  const confirmPassword = document.querySelector('input[name="confirm_password"]');

// ... rest of the file
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
    if (username.value.trim().length < 4) return alert("Username must be at least 4 characters.");
    if (!usernameAvailable) return alert("Username is not available. Please choose another.");
    const passwordValue = password.value;
    const strongPassword = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    if (!strongPassword.test(passwordValue))
      return alert("Password must include letters, numbers, and a special character.");
    if (password.value !== confirmPassword.value)
      return alert("Passwords do not match. Please check again.");

    if (!confirm("Are you sure you want to sign up with these details?")) return;

    // ✅ Submit form if everything passes
    form.submit();
  });
});
