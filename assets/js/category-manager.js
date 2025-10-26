/**
 * Category Management JavaScript Functions
 * Include this file in your store management pages
 */

class CategoryManager {
  constructor(baseUrl = "php/") {
    this.baseUrl = baseUrl;
    this.categories = [];
    this.currentPage = 1;
    this.totalPages = 1;
  }

  /**
   * Create a new category
   * @param {Object} categoryData - The category data
   * @param {string} categoryData.name - Category name
   * @param {string} categoryData.description - Category description (optional)
   * @returns {Promise<Object>} API response
   */
  async createCategory(categoryData) {
    try {
      const response = await fetch(`${this.baseUrl}create_category.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(categoryData),
      });

      const result = await response.json();

      if (result.success) {
        // Refresh categories list after successful creation
        await this.loadCategories();
      }

      return result;
    } catch (error) {
      console.error("Error creating category:", error);
      return {
        success: false,
        message: "Network error occurred while creating category",
      };
    }
  }

  /**
   * Load categories with pagination and search
   * @param {Object} options - Query options
   * @param {number} options.page - Page number (default: 1)
   * @param {number} options.limit - Items per page (default: 20)
   * @param {string} options.search - Search term (optional)
   * @param {string} options.sortBy - Sort column (default: 'name')
   * @param {string} options.sortOrder - Sort order 'asc' or 'desc' (default: 'asc')
   * @returns {Promise<Object>} API response
   */
  async loadCategories(options = {}) {
    const params = new URLSearchParams({
      page: options.page || this.currentPage,
      limit: options.limit || 20,
      sort_by: options.sortBy || "name",
      sort_order: options.sortOrder || "asc",
    });

    if (options.search) {
      params.append("search", options.search);
    }

    try {
      const response = await fetch(
        `${this.baseUrl}get_categories.php?${params}`
      );
      const result = await response.json();

      if (result.success) {
        this.categories = result.data;
        this.currentPage = result.pagination.current_page;
        this.totalPages = result.pagination.total_pages;
      }

      return result;
    } catch (error) {
      console.error("Error loading categories:", error);
      return {
        success: false,
        message: "Network error occurred while loading categories",
      };
    }
  }

  /**
   * Get all categories (without pagination)
   * @returns {Promise<Array>} Array of categories
   */
  async getAllCategories() {
    const result = await this.loadCategories({ limit: 1000 });
    return result.success ? result.data : [];
  }

  /**
   * Populate a select element with categories
   * @param {string|HTMLElement} selectElement - Select element or selector
   * @param {Object} options - Options
   * @param {boolean} options.includeEmpty - Include empty option (default: true)
   * @param {string} options.emptyText - Text for empty option (default: 'Select a category')
   * @param {number} options.selectedId - ID of selected category (optional)
   */
  async populateSelect(selectElement, options = {}) {
    const select =
      typeof selectElement === "string"
        ? document.querySelector(selectElement)
        : selectElement;

    if (!select) {
      console.error("Select element not found");
      return;
    }

    const categories = await this.getAllCategories();

    // Clear existing options
    select.innerHTML = "";

    // Add empty option
    if (options.includeEmpty !== false) {
      const emptyOption = document.createElement("option");
      emptyOption.value = "";
      emptyOption.textContent = options.emptyText || "Select a category";
      select.appendChild(emptyOption);
    }

    // Add category options
    categories.forEach((category) => {
      const option = document.createElement("option");
      option.value = category.id;
      option.textContent = `${category.name} (${category.item_count} items)`;

      if (options.selectedId && category.id == options.selectedId) {
        option.selected = true;
      }

      select.appendChild(option);
    });
  }

  /**
   * Render categories in a table
   * @param {string|HTMLElement} tableElement - Table element or selector
   * @param {Object} options - Render options
   */
  renderTable(tableElement, options = {}) {
    const table =
      typeof tableElement === "string"
        ? document.querySelector(tableElement)
        : tableElement;

    if (!table) {
      console.error("Table element not found");
      return;
    }

    const tbody = table.querySelector("tbody") || table;
    tbody.innerHTML = "";

    if (this.categories.length === 0) {
      const row = tbody.insertRow();
      const cell = row.insertCell();
      cell.colSpan = 5;
      cell.textContent = "No categories found";
      cell.style.textAlign = "center";
      cell.style.padding = "20px";
      cell.style.color = "#666";
      return;
    }

    this.categories.forEach((category) => {
      const row = tbody.insertRow();

      // ID
      const idCell = row.insertCell();
      idCell.textContent = category.id;

      // Name
      const nameCell = row.insertCell();
      nameCell.textContent = category.name;

      // Description
      const descCell = row.insertCell();
      descCell.textContent = category.description || "No description";

      // Item Count
      const countCell = row.insertCell();
      countCell.textContent = category.item_count;

      // Created
      const createdCell = row.insertCell();
      createdCell.textContent = `${category.created_at} by ${category.created_by}`;

      // Actions (if callback provided)
      if (options.actionsCallback) {
        const actionsCell = row.insertCell();
        actionsCell.innerHTML = options.actionsCallback(category);
      }
    });
  }

  /**
   * Render pagination controls
   * @param {string|HTMLElement} containerElement - Container element or selector
   * @param {Function} onPageChange - Callback function for page changes
   */
  renderPagination(containerElement, onPageChange) {
    const container =
      typeof containerElement === "string"
        ? document.querySelector(containerElement)
        : containerElement;

    if (!container) {
      console.error("Pagination container not found");
      return;
    }

    container.innerHTML = "";

    if (this.totalPages <= 1) {
      return;
    }

    const pagination = document.createElement("div");
    pagination.className = "pagination";

    // Previous button
    if (this.currentPage > 1) {
      const prevBtn = document.createElement("button");
      prevBtn.textContent = "Previous";
      prevBtn.onclick = () => onPageChange(this.currentPage - 1);
      pagination.appendChild(prevBtn);
    }

    // Page numbers
    const startPage = Math.max(1, this.currentPage - 2);
    const endPage = Math.min(this.totalPages, this.currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
      const pageBtn = document.createElement("button");
      pageBtn.textContent = i;
      pageBtn.className = i === this.currentPage ? "active" : "";
      pageBtn.onclick = () => onPageChange(i);
      pagination.appendChild(pageBtn);
    }

    // Next button
    if (this.currentPage < this.totalPages) {
      const nextBtn = document.createElement("button");
      nextBtn.textContent = "Next";
      nextBtn.onclick = () => onPageChange(this.currentPage + 1);
      pagination.appendChild(nextBtn);
    }

    container.appendChild(pagination);
  }

  /**
   * Show category creation modal/form
   * This is a basic implementation - customize as needed
   */
  showCreateForm() {
    const formHtml = `
            <div class="modal" id="categoryModal">
                <div class="modal-content">
                    <h3>Create New Category</h3>
                    <form id="createCategoryForm">
                        <div class="form-group">
                            <label for="categoryName">Category Name *</label>
                            <input type="text" id="categoryName" name="name" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="categoryDescription">Description</label>
                            <textarea id="categoryDescription" name="description" maxlength="1000"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit">Create Category</button>
                            <button type="button" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

    // Remove existing modal if any
    const existingModal = document.getElementById("categoryModal");
    if (existingModal) {
      existingModal.remove();
    }

    // Add modal to body
    document.body.insertAdjacentHTML("beforeend", formHtml);

    // Handle form submission
    document
      .getElementById("createCategoryForm")
      .addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const categoryData = {
          name: formData.get("name"),
          description: formData.get("description"),
        };

        const result = await this.createCategory(categoryData);

        if (result.success) {
          alert("Category created successfully!");
          document.getElementById("categoryModal").remove();
          // Trigger any callback if provided
          if (this.onCategoryCreated) {
            this.onCategoryCreated(result.category);
          }
        } else {
          alert("Error: " + result.message);
        }
      });
  }
}

// Export for use in other files
if (typeof module !== "undefined" && module.exports) {
  module.exports = CategoryManager;
}

// Global instance for direct use
window.categoryManager = new CategoryManager();

// Example usage:
/*
// Initialize category manager
const categoryManager = new CategoryManager();

// Load and display categories
categoryManager.loadCategories().then(result => {
    if (result.success) {
        categoryManager.renderTable('#categoriesTable');
        categoryManager.renderPagination('#pagination', (page) => {
            categoryManager.loadCategories({ page }).then(() => {
                categoryManager.renderTable('#categoriesTable');
                categoryManager.renderPagination('#pagination', arguments.callee);
            });
        });
    }
});

// Populate a select dropdown
categoryManager.populateSelect('#categorySelect');

// Create a new category
categoryManager.createCategory({
    name: 'New Category',
    description: 'Category description'
}).then(result => {
    if (result.success) {
        console.log('Category created:', result.category);
    }
});
*/
