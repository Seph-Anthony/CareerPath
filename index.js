// Existing Dropdown Logic for Sign In
const dropdown = document.getElementById("dropdownId");

dropdown.addEventListener('change', function() {
    if (this.value === "option1") {
        window.open('signinstudent.html');
    } else if (this.value === "option2") {
        window.open('signincompany.html');
    } else if (this.value === "option3") {
        window.open('signincoordinator.html');
    }
    
    this.value = "";
});


// --- NEW MODAL LOGIC ---

// Get the modal element
const modal = document.getElementById("signupModal");

// Function to open the modal
function openSignupModal() {
    modal.style.display = "block";
}

// Function to close the modal
function closeSignupModal() {
    modal.style.display = "none";
}

// Close the modal if the user clicks anywhere outside of it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Expose the functions to the global scope (since they are called from HTML onclick)
window.openSignupModal = openSignupModal;
window.closeSignupModal = closeSignupModal;