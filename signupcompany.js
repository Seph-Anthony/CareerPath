document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".signup-form");

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const username = form.username.value.trim();
    const password = form.password.value.trim();
    const confirmPassword = form.confirm_password.value.trim();

    // Basic validations
    if (!username || !password || !confirmPassword) {
      alert("Please fill in all fields.");
      return;
    }

    if (password !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }

    // 🔹 Check username duplication
    try {
      const response = await fetch("http://localhost/careerpath/check_username.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `username=${encodeURIComponent(username)}`
      });

      const result = await response.text();
      console.log("Response from server:", result);

      if (result.includes("taken")) {
        alert("That username is already taken. Please choose another one.");
        return;
      } else if (result.includes("error")) {
        alert("Error checking username. Please try again later.");
        return;
      }
    } catch (err) {
      console.error("Error:", err);
      alert("Could not connect to the server. Please try again.");
      return;
    }

    // Submit the form if all validations pass
    form.submit();
  });
});
