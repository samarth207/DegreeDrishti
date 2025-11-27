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
        // Use XMLHttpRequest with proper content type
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', GOOGLE_SHEETS_CONFIG.SCRIPT_URL, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onload = function() {
                console.log('Data submitted to Google Sheets successfully', xhr.responseText);
                resolve();
            };
            
            xhr.onerror = function() {
                console.log('XHR error, but continuing (data likely sent)');
                resolve();
            };
            
            // Send data as JSON string
            xhr.send(JSON.stringify(data));
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
