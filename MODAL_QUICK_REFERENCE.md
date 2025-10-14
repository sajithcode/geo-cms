# Modal System - Quick Reference Guide

## Available Modals

### 1. Confirm Modal (All Dashboards)

**Purpose:** Replace `confirm()` - Yes/No confirmations

**Usage:**

```javascript
showConfirmModal("Title Here", "Your message here", () => {
  // Action on confirmation
  console.log("User confirmed!");
});
```

**Example:**

```javascript
showConfirmModal(
  "Delete Reservation",
  "Are you sure you want to delete this reservation? This action cannot be undone.",
  async () => {
    await deleteReservation(id);
    location.reload();
  }
);
```

---

### 2. Lab Status Modal (Admin Only)

**Purpose:** Replace `prompt()` - Change lab status

**Usage:**

```javascript
// Set lab ID
document.getElementById("status-lab-id").value = labId;

// Show modal
showModal("lab-status-modal");
```

**Form is automatically handled** - no additional code needed!

---

### 3. Reservation Details Modal (All Dashboards)

**Purpose:** Replace `alert()` - Show detailed information

**Usage:**

```javascript
async function viewReservationDetails(reservationId) {
  const response = await fetch(
    `php/labs_api.php?action=get_reservation_details&reservation_id=${reservationId}`
  );
  const result = await response.json();

  if (result.success) {
    displayReservationDetails(result.reservation);
    showModal("reservation-details-modal");
  }
}
```

---

### 4. Approval Modal (Admin/Staff)

**Purpose:** Approve or reject reservations with notes

**Usage:**

```javascript
// For approval
showApprovalModal(reservationId, "approve");

// For rejection
showApprovalModal(reservationId, "reject");
```

---

## Core Modal Functions

### Open Modal

```javascript
showModal("modal-id");
```

### Close Modal

```javascript
hideModal("modal-id");
```

### Show Loading

```javascript
showLoading("Processing...");
```

### Hide Loading

```javascript
hideLoading();
```

### Show Notification

```javascript
showNotification("Message here", "success"); // or 'error', 'warning', 'info'
```

---

## Common Patterns

### Pattern 1: Confirm Before Action

```javascript
showConfirmModal("Confirm Action", "Are you sure?", async () => {
  showLoading("Processing...");
  await performAction();
  hideLoading();
  showNotification("Success!", "success");
  location.reload();
});
```

### Pattern 2: Form in Modal

```javascript
// HTML
<form id="my-form">
    <input type="text" id="field1" required>
    <button type="submit">Save</button>
</form>

// JavaScript
document.getElementById('my-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const data = new FormData(e.target);

    showLoading('Saving...');
    const response = await fetch('api.php', { method: 'POST', body: data });
    const result = await response.json();
    hideLoading();

    if (result.success) {
        hideModal('my-modal');
        showNotification(result.message, 'success');
    } else {
        showNotification(result.message, 'error');
    }
});
```

### Pattern 3: Load Data and Show Modal

```javascript
async function showDetailsModal(itemId) {
  showLoading("Loading...");

  const response = await fetch(`api.php?id=${itemId}`);
  const data = await response.json();

  hideLoading();

  if (data.success) {
    // Populate modal
    document.getElementById("detail-content").innerHTML = data.html;
    showModal("details-modal");
  } else {
    showNotification("Failed to load data", "error");
  }
}
```

---

## Migration Cheat Sheet

| Old Browser Method       | New Modal Method                                 |
| ------------------------ | ------------------------------------------------ |
| `alert('Message')`       | `showNotification('Message', 'info')`            |
| `confirm('Message?')`    | `showConfirmModal('Title', 'Message', callback)` |
| `prompt('Enter value:')` | Create custom modal with input field             |

---

## Styling Notes

### Modal Sizes

- Default: `max-width: 500px`
- Large: Add `.modal-lg` class → `max-width: 800px`
- Small: Add `.modal-sm` class → `max-width: 300px`

### Custom Buttons

```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-secondary">Secondary</button>
```

---

## Troubleshooting

### Modal Doesn't Open

✓ Check modal ID matches  
✓ Verify `showModal()` function exists  
✓ Check browser console for errors

### Modal Doesn't Close

✓ Verify `hideModal()` is called  
✓ Check close button onclick attribute  
✓ Ensure modal backdrop click is handled

### Form Doesn't Submit

✓ Check form has ID  
✓ Verify event listener is attached  
✓ Use `e.preventDefault()` in handler  
✓ Check required fields are filled

### Callback Doesn't Execute

✓ Ensure callback is a function  
✓ Check for JavaScript errors  
✓ Verify hideModal is called first  
✓ Use arrow functions to preserve context

---

## Best Practices

1. **Always provide meaningful titles** - Help users understand the action
2. **Keep messages clear and concise** - Don't overwhelm with text
3. **Use appropriate button colors** - Red for destructive, green for positive
4. **Show loading states** - Let users know something is happening
5. **Provide feedback** - Show success/error notifications after actions
6. **Handle errors gracefully** - Always have try-catch blocks
7. **Close modals after success** - Don't leave them open
8. **Reload or update data** - Keep the UI in sync with backend

---

## Keyboard Shortcuts

- **ESC** - Close modal (if implemented)
- **Enter** - Submit form in modal
- **Tab** - Navigate between fields

---

## Accessibility Tips

- Use semantic HTML
- Include aria-labels on buttons
- Ensure keyboard navigation works
- Test with screen readers
- Maintain focus within modal
- Return focus after closing

---

**Quick Help:** If you see `alert()`, `confirm()`, or `prompt()` in the code, replace them using this guide!
