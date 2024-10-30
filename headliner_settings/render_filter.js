/**
 * Scripts for category and tag filter render functions
 */

/**
 * Selects or deselects all checkboxes for the given option
 *
 * @param {string} taxonomyOption
 * @param {boolean} checkAll
 */
function toggleCheckboxes(taxonomyOption, checkAll) {
    const checkboxes = document.querySelectorAll(`input[name="headliner_plugin_options[${taxonomyOption}][]"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = checkAll;
    });
}


/**
 * Updates tag and category checkboxes based on the value of
 * show in all posts option
 */
function updateCategoryTagFilters() {
    const checkboxes = document.querySelectorAll(`input[type="checkbox"][name^="headliner_plugin_options[allowed_"]`);
    const showInAllPostsYes = document.querySelector(`input[name="headliner_plugin_options[show_in_all_posts]"][value="yes"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = showInAllPostsYes.checked;
    });
}

/**
 * Toggles filter settings visibility based on show in all posts value
 *
 * @param {string} showInAllPostsValue
 */
function toggleSettingsVisibility(showInAllPostsValue) {
    const tagSettings = document.querySelector('#tag_filter_settings');
    const categorySettings = document.querySelector('#category_filter_settings');
    const shouldHide = showInAllPostsValue === 'yes';
    tagSettings.style.display = categorySettings.style.display = shouldHide ? 'none' : 'block';
}

/**
 * Initializes settings visibility and handles user interaction
 */
function initializePage() {
    const showInAllPostsOption = document.querySelectorAll('input[name="headliner_plugin_options[show_in_all_posts]"]');
    const initialShowInAllPostsValue = document.querySelector('input[name="headliner_plugin_options[show_in_all_posts]"]:checked').value;

    toggleSettingsVisibility(initialShowInAllPostsValue);
    showInAllPostsOption.forEach(option => {
        option.addEventListener('change', function() {
            toggleSettingsVisibility(this.value);
            updateCategoryTagFilters();
        });
    });
}

document.addEventListener('DOMContentLoaded', initializePage);
