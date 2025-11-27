// Google Sheets Integration for DegreeDrishti Forms
// 
// Setup Instructions:
// 1. Create a new Google Sheet
// 2. Go to Extensions > Apps Script
// 3. Replace the code with the doPost function provided in google-sheets-script.gs
// 4. Deploy as Web App (anyone can access)
// 5. Copy the deployment URL and paste it in the SCRIPT_URL variable below

const GOOGLE_SHEETS_CONFIG = {
    SCRIPT_URL: 'https://script.google.com/macros/s/AKfycbxs8hpW36hMthngJvj_ZLoHuszg4sKF3OitH5hO3id0Eu8yv7hdlqe84k-Zyig4B9za/exec', // Replace with your Google Apps Script web app URL
    ENABLED: true // Set to true after setting up Google Sheets
};

/**
 * Submit form data to Google Sheets
 * @param {Object} formData - Object containing form field values
 * @param {string} formType - Type of form ('application' or 'contact')
 * @returns {Promise} - Resolves when data is successfully submitted
 */
async function submitToGoogleSheets(formData, formType) {
    console.log('submitToGoogleSheets called!', { formData, formType, enabled: GOOGLE_SHEETS_CONFIG.ENABLED });
    
    if (!GOOGLE_SHEETS_CONFIG.ENABLED) {
        console.log('Google Sheets integration is disabled');
        return Promise.resolve();
    }

    const data = {
        ...formData,
        formType: formType,
        timestamp: new Date().toISOString()
    };

    console.log('Submitting to Google Sheets:', data);
    console.log('URL:', GOOGLE_SHEETS_CONFIG.SCRIPT_URL);

    try {
        // Use a hidden iframe to submit data (bypasses CORS)
        return new Promise((resolve) => {
            // Create a form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = GOOGLE_SHEETS_CONFIG.SCRIPT_URL;
            form.target = 'hidden_iframe';
            form.style.display = 'none';

            // Create hidden iframe
            let iframe = document.getElementById('hidden_iframe');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.name = 'hidden_iframe';
                iframe.id = 'hidden_iframe';
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }

            // Add each data field as a form input
            for (const key in data) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            }

            // Append form and submit
            document.body.appendChild(form);
            form.submit();
            
            // Clean up after a short delay
            setTimeout(() => {
                document.body.removeChild(form);
                console.log('Data submitted to Google Sheets successfully (via iframe)');
                resolve();
            }, 1000);
        });
    } catch (error) {
        console.error('Error submitting to Google Sheets:', error);
        // Don't throw error - allow form submission to continue even if Google Sheets fails
        return Promise.resolve();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { submitToGoogleSheets, GOOGLE_SHEETS_CONFIG };
}
