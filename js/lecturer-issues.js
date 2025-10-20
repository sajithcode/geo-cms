// Lecturer Issues Dashboard JavaScript (same as student with role-specific features)
// This extends the student functionality with lecturer-specific features

// Import the base student functionality
// Since lecturers have the same interface as students, we'll reuse the student manager

class LecturerIssuesManager extends StudentIssuesManager {
  constructor() {
    super();
    this.userRole = "lecturer";
  }

  // Override or extend methods specific to lecturers if needed
  init() {
    super.init();
    // Add any lecturer-specific initialization
  }

  // Lecturers might have additional features like viewing other users' issues in their classes
  async loadClassIssues() {
    // Future enhancement: load issues from students in lecturer's classes
    try {
      const response = await fetch(
        "php/issues_api.php?action=get_class_issues"
      );
      const data = await response.json();

      if (data.success) {
        // Handle class issues display
        console.log("Class issues loaded:", data.issues);
      }
    } catch (error) {
      console.error("Error loading class issues:", error);
    }
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  window.issuesManager = new LecturerIssuesManager();
});
