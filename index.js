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