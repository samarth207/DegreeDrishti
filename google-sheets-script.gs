// Google Apps Script for receiving form data
// 
// Setup Instructions:
// 1. Create a new Google Sheet
// 2. Go to Extensions > Apps Script
// 3. Replace any existing code with this entire file
// 4. Save the script (File > Save)
// 5. Deploy as Web App:
//    - Click Deploy > New deployment
//    - Select type: Web app
//    - Execute as: Me
//    - Who has access: Anyone
//    - Click Deploy
// 6. Copy the Web App URL and paste it in google-sheets-integration.js

function doPost(e) {
  try {
    var data;
    
    // Try to parse JSON from postData
    if (e.postData && e.postData.contents) {
      data = JSON.parse(e.postData.contents);
    } 
    // Fallback to parameter data
    else if (e.parameter && e.parameter.data) {
      data = JSON.parse(decodeURIComponent(e.parameter.data));
    } 
    // Direct parameters
    else if (e.parameters) {
      data = {};
      for (var key in e.parameters) {
        data[key] = e.parameters[key][0];
      }
    }
    else {
      throw new Error('No data received');
    }
    
    // Get the active spreadsheet
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    
    // Determine which sheet to use based on form type
    var sheetName = data.formType === 'contact' ? 'Contact Forms' : 'Applications';
    var sheet = ss.getSheetByName(sheetName);
    
    // If sheet doesn't exist, create it
    if (!sheet) {
      sheet = ss.insertSheet(sheetName);
      
      // Add headers based on form type
      if (data.formType === 'contact') {
        sheet.appendRow(['Timestamp', 'First Name', 'Last Name', 'Email', 'Country Code', 'Phone', 'Subject', 'Message']);
      } else {
        sheet.appendRow(['Timestamp', 'First Name', 'Last Name', 'Email', 'Country Code', 'Phone', 'State', 'Course', 'University']);
      }
    }
    
    // Prepare the row data
    var rowData = [];
    
    if (data.formType === 'contact') {
      rowData = [
        data.timestamp || new Date().toISOString(),
        data.firstName || '',
        data.lastName || '',
        data.email || '',
        data.countryCode || '',
        data.phone || '',
        data.subject || '',
        data.message || ''
      ];
    } else {
      rowData = [
        data.timestamp || new Date().toISOString(),
        data.firstName || '',
        data.lastName || '',
        data.email || '',
        data.countryCode || '',
        data.phone || '',
        data.state || '',
        data.course || '',
        data.university || ''
      ];
    }
    
    // Append the data to the sheet
    sheet.appendRow(rowData);
    
    // Return success response
    return ContentService.createTextOutput(JSON.stringify({
      'status': 'success',
      'message': 'Data added successfully'
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (error) {
    // Return error response
    return ContentService.createTextOutput(JSON.stringify({
      'status': 'error',
      'message': error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// Test function to verify the script works
function testPost() {
  var testData = {
    formType: 'application',
    timestamp: new Date().toISOString(),
    firstName: 'Test',
    lastName: 'User',
    email: 'test@example.com',
    countryCode: '+91',
    phone: '9876543210',
    state: 'Maharashtra',
    course: 'MBA',
    university: 'Amity University'
  };
  
  var e = {
    postData: {
      contents: JSON.stringify(testData)
    }
  };
  
  return doPost(e);
}
