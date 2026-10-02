# Course Pages Integration Guide

This guide explains how to integrate the dynamic university loading system into course pages.

## Overview

The course pages now use a dynamic system that loads universities from the admin dashboard database. This allows admins to:
- Add/remove universities from the dashboard
- Assign courses to universities
- Update fees, rankings, and other details
- Changes reflect immediately on the course pages

## Files Created

1. **`/api/get-course-universities.php`** - API endpoint that fetches universities offering a specific course
2. **`/courses/course-universities.js`** - JavaScript that loads universities and populates the table
3. **`/courses/course-universities.css`** - Styles for the university table
4. **`/courses/mba.html`** - Updated as an example implementation

## How to Apply to Other Course Pages

### Step 1: Add the CSS Link

In the `<head>` section of your course HTML file, add:
```html
<link rel="stylesheet" href="course-universities.css">
```

### Step 2: Update the Table Structure

Replace your static university table with this dynamic version:

```html
<table id="universitiesTable" data-course="COURSE_NAME">
    <thead>
        <tr>
            <th>University</th>
            <th>Fees (Total)</th>
            <th>Duration</th>
            <th>Ranking</th>
            <th>Average Placement</th>
            <th>Degree Approvals</th>
            <th>Website</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <!-- Universities will be loaded dynamically from API -->
    </tbody>
</table>
```

**Important:** Replace `COURSE_NAME` with the actual course name (e.g., "MCA", "BBA", "BCA"). This is case-insensitive.

### Step 3: Add the JavaScript

Before the closing `</body>` tag, add:
```html
<script src="course-universities.js"></script>
```

### Step 4: Remove Static Rows

Delete all the static `<tr>` elements from the `<tbody>`. The system will populate them dynamically.

## Course Name Mapping

The `data-course` attribute should match course names in the admin dashboard. Common mappings:

| Page Slug | data-course Value |
|-----------|------------------|
| mba.html | MBA |
| mca.html | MCA |
| bba.html | BBA |
| bca.html | BCA |
| bcom.html | BCom |
| mcom.html | MCom |
| ba.html | BA |
| executive-mba.html | Executive MBA |
| mba-dual.html | MBA (Dual Specification) |
| mba-wx.html | MBA (WX) |
| msc-data-science.html | MSc (Data Science) |
| ma-journalism.html | MA (Journalism) |
| ma-public-policy.html | MA (Public Policy) |
| bca-mca.html | BCA + MCA |
| bba-mba.html | BBA + MBA |
| bcom-mba.html | B.Com + MBA |
| bcom-acca.html | B.Com + ACCA |
| diploma-digital-marketing.html | Diploma in Digital Marketing |
| diploma-financial-management.html | Diploma in Financial Management |
| diploma-business-analytics.html | Diploma in Business Analytics |

## Admin Dashboard Integration

### Adding Universities to a Course

1. Go to `/admin/universities.php`
2. Edit or add a university
3. In the "Courses Offered" section, add the course name:
   - **Course**: MBA (or MCA, BBA, etc.)
   - **Duration**: 2 Years
   - **Annual Fee**: 75000
   - **Total Fee**: 150000
   - **Specializations**: Finance, Marketing, HR (comma-separated)

4. Save the university

### Removing a University from a Course

1. Edit the university in the admin dashboard
2. Remove the course row from the "Courses Offered" table
3. Save the university

### Updating Course Details

Simply edit the course information in the admin dashboard:
- Update fees
- Change duration
- Add/remove specializations
- All changes reflect immediately on the course page

## Customization

### Modifying the Table Columns

If you need different columns, edit `course-universities.js`:

```javascript
function generateUniversityRow(uni) {
    // Modify the HTML structure here
    return `
        <tr>
            <td>...</td>
            <!-- Add/remove columns as needed -->
        </tr>
    `;
}
```

### Adding Filters

You can add filters to the course page by modifying `course-universities.js` to filter the data before rendering.

## Troubleshooting

### Universities Not Loading

1. Check browser console for errors
2. Verify the API endpoint is accessible: `/api/get-course-universities.php?course=MBA`
3. Ensure the `data-course` attribute matches the course name in the database
4. Check that universities have the course in their `courses_json` field

### Styling Issues

1. Ensure `course-universities.css` is loaded
2. Check for CSS conflicts with existing styles
3. Use browser dev tools to inspect elements

### Course Name Not Matching

The system uses case-insensitive matching. If universities aren't showing:
1. Check the exact course name in the admin dashboard
2. Ensure it matches the `data-course` value (case-insensitive)
3. Common issues: "MBA" vs "M.B.A." vs "Master of Business Administration"

## Benefits

1. **Single Source of Truth**: All university data comes from the admin dashboard
2. **Easy Updates**: Admins can update everything without touching code
3. **Consistency**: Same data across compare page and course pages
4. **Flexibility**: Add/remove universities per course easily
5. **No Code Changes**: Content updates don't require developer intervention
