document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('multiStepForm');
    const nextButton = document.querySelector('.step-1 .next');

    nextButton.addEventListener('click', function (e) {
       

        const inputs = document.querySelectorAll('.step-1 input');
        let isValid = true;
        let firstEmptyInput = null;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.border = '1px solid red';
                if (!firstEmptyInput) firstEmptyInput = input;

                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('error-message')) {
                    const errorMsg = document.createElement('span');
                    errorMsg.className = 'error-message';
                    errorMsg.style.color = 'red';
                    errorMsg.style.display = 'block';
                    errorMsg.style.marginTop = '5px';
                    errorMsg.textContent = 'Ce champ est requis';
                    input.insertAdjacentElement('afterend', errorMsg);
                }
            } else {
                input.style.border = '';
                if (input.nextElementSibling && input.nextElementSibling.classList.contains('error-message')) {
                    input.nextElementSibling.remove();
                }
            }
        });

        if (!isValid) {
            alert('Veuillez remplir tous les champs requis avant de continuer.');
            firstEmptyInput.focus(); // 🔥 Focus on the first empty input
            return; // 🔴 Stop here — don’t continue to the next step!
        }

        // ✅ Only go to next step if form is valid
        // Example:
        goToStep(2);
    });

    const inputs = document.querySelectorAll('.step-1 input');
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            if (this.value.trim()) {
                this.style.border = '';
                if (this.nextElementSibling && this.nextElementSibling.classList.contains('error-message')) {
                    this.nextElementSibling.remove();
                }
            }
        });
    });
});







document.addEventListener("DOMContentLoaded", function () {
    const steps = document.querySelectorAll(".step");
    const nextButtons = document.querySelectorAll(".next");
    const prevButtons = document.querySelectorAll(".prev");

    nextButtons.forEach((button, index) => {
        button.addEventListener("click", function () {
            const currentStep = steps[index];
            const nextStep = steps[index + 1];
    
            // 🛑 Handle validation only on step 1
            if (currentStep.classList.contains("step-1")) {
                const inputs = currentStep.querySelectorAll('input');
                let isValid = true;
                let firstEmptyInput = null;
    
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.border = '1px solid red';
                        if (!firstEmptyInput) firstEmptyInput = input;
    
                        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('error-message')) {
                            const errorMsg = document.createElement('span');
                            errorMsg.className = 'error-message';
                            errorMsg.style.color = 'red';
                            errorMsg.style.display = 'block';
                            errorMsg.style.marginTop = '5px';
                            errorMsg.textContent = 'Ce champ est requis';
                            input.insertAdjacentElement('afterend', errorMsg);
                        }
                    } else {
                        input.style.border = '';
                        if (input.nextElementSibling && input.nextElementSibling.classList.contains('error-message')) {
                            input.nextElementSibling.remove();
                        }
                    }
                });
    
                if (!isValid) {
                    alert("Veuillez remplir tous les champs requis avant de continuer.");
                    firstEmptyInput.focus();
                    return; // 🛑 Stop transition
                }
            }
    
            // ✅ All good, move to next step
            if (nextStep) {
                currentStep.classList.remove("active");
                nextStep.classList.add("active");
            }
        });
    });
    
    

    prevButtons.forEach((button, index) => {
        button.addEventListener("click", function () {
            const currentStep = steps[index + 1];
            const prevStep = steps[index];

            if (prevStep) {
                currentStep.classList.remove("active");
                prevStep.classList.add("active");
            }
        });
    });
});

document.getElementById("multiStepForm").addEventListener("submit", function(event) {

    
    // Hide the form
    document.querySelector(".form-container").style.display = "none";
    
    // Show the thank you message
    document.getElementById("thankYouMessage").style.display = "block";
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("intervenants_oui");
    const nonRadio = document.getElementById("intervenants_non");
    const textarea = document.getElementById("intervenants_details");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("Logistique_oui");
    const nonRadio = document.getElementById("Logistique_non");
    const textarea = document.getElementById("Logistique");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("hebergement_oui");
    const nonRadio = document.getElementById("hebergement_non");
    const textarea = document.getElementById("hebergement");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("promotion_oui");
    const nonRadio = document.getElementById("promotion_non");
    const textarea = document.getElementById("promotion");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("accueil_oui");
    const nonRadio = document.getElementById("accueil_non");
    const textarea = document.getElementById("accueil");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("restauration_oui");
    const nonRadio = document.getElementById("restauration_non");
    const textarea = document.getElementById("restauration");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const ouiRadio = document.getElementById("gadgets_oui");
    const nonRadio = document.getElementById("gadgets_non");
    const textarea = document.getElementById("gadgets");

    function toggleTextarea() {
        textarea.style.display = ouiRadio.checked ? "block" : "none";
    }

    // Initialize visibility based on default checked value
    toggleTextarea();

    ouiRadio.addEventListener("change", toggleTextarea);
    nonRadio.addEventListener("change", toggleTextarea);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("location_salle");
    const div = document.getElementById("Location_salle_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("equipement_audiovisuel");
    const div = document.getElementById("equipement_audiovisuel_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("transports");
    const div = document.getElementById("transports_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("decoration");
    const div = document.getElementById("decoration_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("Campagnes");
    const div = document.getElementById("Campagnes_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("promdig");
    const div = document.getElementById("promdig_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("Shooting");
    const div = document.getElementById("Shooting_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("imp");
    const div = document.getElementById("imp_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("securite");
    const div = document.getElementById("securite_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});

document.addEventListener("DOMContentLoaded", function () {
    const checkbox = document.getElementById("Animations");
    const div = document.getElementById("Animations_div");

    function toggleDiv() {
        div.style.display = checkbox.checked ? "block" : "none";
    }

    // Initialize state on page load
    toggleDiv();

    // Listen for changes
    checkbox.addEventListener("change", toggleDiv);
});