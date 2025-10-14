# Debugging Guide: Reservation Details Error

## Error Message

```
An error occurred while loading reservation details ×
```

## Recent Changes Made

### 1. Enhanced Error Logging (js/labs.js)

Added detailed console logging to help identify the exact issue:

```javascript
async function viewReservationDetails(reservationId) {
  console.log("Fetching reservation details for ID:", reservationId);
  // ... more logs
  console.log("Response status:", response.status);
  console.log("API Result:", result);
}
```

### 2. Defensive displayReservationDetails Function

Added try-catch and better null handling:

- HTML escaping function
- Error boundary
- Console logging
- Fallback display on error

## How to Debug

### Step 1: Open Browser Console

1. Open the student dashboard
2. Press F12 to open Developer Tools
3. Go to Console tab
4. Click "View" on any reservation

### Step 2: Check Console Logs

You should see:

```
Fetching reservation details for ID: [number]
Response status: 200
API Result: {success: true, reservation: {...}}
Displaying reservation: {...}
```

### Step 3: Identify the Issue

#### If you see "404" or "500" status:

- **Problem:** API endpoint not found or server error
- **Fix:** Check that `labs/php/labs_api.php` file exists
- **Check:** Verify file permissions

#### If you see "Unauthorized access":

- **Problem:** Session issue or permission problem
- **Fix:** Check that user is logged in
- **Check:** Verify user_id in session matches reservation

#### If you see "Reservation not found":

- **Problem:** Invalid reservation ID
- **Fix:** Check database has the reservation
- **Check:** SQL query in labs_api.php line 253-263

#### If you see JavaScript error:

- **Problem:** Missing function or variable
- **Fix:** Check that all required scripts are loaded
- **Check:** script.js loaded before labs.js

### Step 4: Common Issues and Fixes

#### Issue: CORS Error

```
Access to fetch at 'php/labs_api.php' from origin...
```

**Fix:** Not applicable for same-origin requests

#### Issue: JSON Parse Error

```
Unexpected token < in JSON at position 0
```

**Fix:** PHP is outputting HTML error instead of JSON

- Check PHP error log
- Ensure no `echo` statements before JSON output
- Check for PHP syntax errors

#### Issue: Modal Not Opening

```
Cannot read properties of null (reading 'innerHTML')
```

**Fix:** Container element missing

- Verify `<div id="reservation-details-content">` exists
- Check modal HTML structure in dashboard file

#### Issue: formatDate Error

```
formatDate is not defined
```

**Fix:** Function not loaded

- Ensure labs.js is loaded
- Check for script loading order

## Quick Test Commands

### Test 1: Check if API is accessible

Open browser console and run:

```javascript
fetch("php/labs_api.php?action=get_reservation_details&reservation_id=1")
  .then((r) => r.json())
  .then(console.log)
  .catch(console.error);
```

### Test 2: Check if modal exists

```javascript
console.log(document.getElementById("reservation-details-modal"));
console.log(document.getElementById("reservation-details-content"));
```

### Test 3: Check if functions exist

```javascript
console.log(typeof viewReservationDetails);
console.log(typeof displayReservationDetails);
console.log(typeof formatDate);
console.log(typeof showModal);
```

## Expected Console Output (Success)

```
Fetching reservation details for ID: 5
Response status: 200
API Result: {
  success: true,
  reservation: {
    id: "5",
    lab_id: "2",
    lab_name: "Lab 02",
    capacity: "30",
    user_id: "3",
    requester_name: "John Student",
    reservation_date: "2025-10-15",
    start_time: "09:00:00",
    end_time: "11:00:00",
    purpose: "Programming class",
    status: "approved",
    request_date: "2025-10-14 10:30:00",
    approved_by_name: "Admin User"
  }
}
Displaying reservation: {id: "5", lab_name: "Lab 02", ...}
```

## Files Modified

1. **js/labs.js**
   - Added detailed console logging to `viewReservationDetails()`
   - Enhanced `displayReservationDetails()` with error handling
   - Added HTML escaping function
   - Added try-catch blocks

## Database Check

Run this SQL to verify data exists:

```sql
SELECT lr.*, l.name as lab_name, l.capacity,
       u.name as requester_name,
       approver.name as approved_by_name
FROM lab_reservations lr
JOIN labs l ON lr.lab_id = l.id
JOIN users u ON lr.user_id = u.id
LEFT JOIN users approver ON lr.approved_by = approver.id
WHERE lr.id = 1; -- Replace with your reservation ID
```

## API Endpoint Check

Test the API directly by visiting:

```
http://localhost/geo-cms/labs/php/labs_api.php?action=get_reservation_details&reservation_id=1
```

Expected response:

```json
{
  "success": true,
  "reservation": {
    "id": "1",
    "lab_name": "Lab 01",
    ...
  }
}
```

## Session Check

Add this to labs_api.php temporarily (line 10):

```php
error_log("Session user_id: " . ($user_id ?? 'not set'));
error_log("Session role: " . ($user_role ?? 'not set'));
```

Check PHP error log for output.

## Next Steps

1. **Clear browser cache** - Ctrl+F5
2. **Check console logs** - Look for the specific error
3. **Test API directly** - Use the URL test above
4. **Check database** - Verify reservation exists
5. **Verify session** - Ensure user is logged in

## Still Not Working?

Share the console output showing:

1. All console.log messages
2. Any error messages (red text)
3. Network tab showing the API request/response
4. The reservation ID you're trying to view

---

**Status:** Debugging tools added  
**Date:** October 14, 2025  
**Action:** Check browser console for detailed error information
