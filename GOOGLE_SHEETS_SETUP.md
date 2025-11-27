# Google Sheets Integration Setup Guide

This guide will help you connect all forms on your DegreeDrishti website to a Google Sheet.

## What This Does

All form submissions (Application Forms and Contact Forms) will be automatically saved to a Google Sheet, allowing you to:
- Track all leads and inquiries
- Export data for analysis
- Share with your team
- Never lose form submissions

## Step-by-Step Setup

### Step 1: Create a Google Sheet

1. Go to [Google Sheets](https://sheets.google.com)
2. Create a new blank spreadsheet
3. Name it "DegreeDrishti Form Submissions" (or any name you prefer)
4. Keep this tab open

### Step 2: Set Up Google Apps Script

1. In your Google Sheet, click **Extensions** > **Apps Script**
2. A new tab will open with the Apps Script editor
3. Delete any existing code in the editor
4. Open the file `google-sheets-script.gs` from your project
5. Copy ALL the code from `google-sheets-script.gs`
6. Paste it into the Apps Script editor
7. Click **Save** (disk icon) or press `Ctrl+S`
8. Name the project "DegreeDrishti Form Handler"

### Step 3: Deploy as Web App

1. In the Apps Script editor, click **Deploy** > **New deployment**
2. Click the gear icon (⚙️) next to "Select type"
3. Select **Web app**
4. Fill in the deployment settings:
   - **Description**: "DegreeDrishti Form Submissions"
   - **Execute as**: **Me** (your Google account)
   - **Who has access**: **Anyone**
5. Click **Deploy**
6. You may need to authorize the script:
   - Click **Authorize access**
   - Choose your Google account
   - Click **Advanced** > **Go to [project name] (unsafe)**
   - Click **Allow**
7. **IMPORTANT**: Copy the **Web app URL** - it will look like:
   ```
   https://script.google.com/macros/s/XXXXXXXXXXXXX/exec
   ```
8. Save this URL somewhere safe!

### Step 4: Configure Your Website

1. Open the file `google-sheets-integration.js` in your DegreeDrishti project
2. Find this line:
   ```javascript
   SCRIPT_URL: 'YOUR_GOOGLE_APPS_SCRIPT_URL_HERE',
   ```
3. Replace `YOUR_GOOGLE_APPS_SCRIPT_URL_HERE` with your Web app URL from Step 3
4. Change this line:
   ```javascript
   ENABLED: false
   ```
   to:
   ```javascript
   ENABLED: true
   ```
5. Save the file

### Step 5: Add Script to HTML Files

The `google-sheets-integration.js` script needs to be included in your HTML files. This has already been done in:
- `index.html` (homepage application form)
- `contact.html` (contact form)
- `course-application.js` (course application modal)

All forms are now configured to submit data to Google Sheets!

### Step 6: Test the Integration

1. Go to your website
2. Fill out any form (Application or Contact)
3. Submit the form
4. Go back to your Google Sheet
5. You should see two sheets:
   - **Applications** - for course applications
   - **Contact Forms** - for contact form submissions
6. Check if your test submission appears in the appropriate sheet

## Data Collected

### Application Forms
- Timestamp
- First Name
- Last Name
- Email
- Country Code
- Phone Number
- State
- Course
- University (for course-specific applications)

### Contact Forms
- Timestamp
- First Name
- Last Name
- Email
- Country Code
- Phone Number
- Subject
- Message

## Troubleshooting

### Forms submit but data doesn't appear in Google Sheets

1. Check if `ENABLED: true` in `google-sheets-integration.js`
2. Verify the Web app URL is correct
3. Make sure you deployed the Apps Script as "Anyone" can access
4. Check browser console for errors (Press F12)

### "Authorization required" error

1. Go back to Apps Script
2. Run the `testPost` function manually:
   - Select `testPost` from the function dropdown
   - Click Run (▶️)
   - Authorize if prompted
3. Try submitting a form again

### Script deployment issues

1. Make sure you saved the script before deploying
2. Try creating a **New deployment** instead of managing existing deployments
3. Make sure "Execute as: Me" is selected

## Security Notes

- The script URL is public but can only add data to your sheet
- No one can read or modify your sheet without access
- Form data is transmitted securely via HTTPS
- Consider adding reCAPTCHA to prevent spam (optional)

## Optional: Notifications

To receive email notifications when someone submits a form:

1. In Apps Script, add this to the `doPost` function (after `sheet.appendRow(rowData);`):
   ```javascript
   // Send email notification
   MailApp.sendEmail({
     to: "your-email@example.com",
     subject: "New Form Submission - DegreeDrishti",
     body: "New form submission received. Check your Google Sheet for details."
   });
   ```
2. Save and create a new deployment
3. Replace the old URL with the new one in `google-sheets-integration.js`

## Support

If you encounter any issues:
1. Check the browser console for errors (F12 > Console tab)
2. Verify all steps were completed correctly
3. Test the `testPost()` function in Apps Script
4. Make sure your Google Sheet is not at row limit (10 million rows)

Your forms are now connected to Google Sheets! 🎉
