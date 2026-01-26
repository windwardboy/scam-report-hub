document.addEventListener('DOMContentLoaded', function() {
    // Use setTimeout to ensure this runs after the current execution stack clears
    setTimeout(function() { 
        console.log('DOM Loaded (after timeout). Searching for delete form...'); // Debug log
        
        // Find the delete form by its ID
        const deleteForm = document.getElementById('delete-report-form');
        
        if (deleteForm) {
            console.log('Delete form FOUND. Attaching listener...'); // Debug log
            
            // Add an event listener for the 'submit' event
            deleteForm.addEventListener('submit', function(event) {
                console.log('Submit event detected. Showing confirm dialog...'); // Debug log
                
                // Show the confirmation dialog
                const confirmed = confirm('Are you absolutely sure you want to permanently delete this scam report? This action cannot be undone.');
                console.log('Confirm result:', confirmed); // Debug log

                // If the user clicks "Cancel", prevent the form from submitting
                if (!confirmed) {
                    console.log('User cancelled. Preventing default submission.'); // Debug log
                    event.preventDefault(); // Stop the form submission
                } else {
                     console.log('User confirmed. Allowing submission.'); // Debug log
                }
            });
        } else {
            // Log an error if the form wasn't found
            console.error('Delete form with ID "delete-report-form" not found (even after timeout).'); 
        }
    }, 0); // Delay of 0 milliseconds
});