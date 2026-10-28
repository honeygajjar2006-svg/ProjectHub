// Small interactive features for ProjectHub

document.addEventListener("DOMContentLoaded", function () {
    // Search project rows in the table.
    const searchInputs = document.querySelectorAll("[data-table-search]");

    searchInputs.forEach(function (searchInput) {
        searchInput.addEventListener("input", function () {
            const table = document.querySelector(searchInput.dataset.tableSearch);
            if (!table) return;

            const searchText = searchInput.value.toLowerCase();
            const rows = table.querySelectorAll("tbody tr");

            rows.forEach(function (row) {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(searchText) ? "" : "none";
            });
        });
    });

    // Hide success messages after a short time.
    const messages = document.querySelectorAll(".alert-success");

    messages.forEach(function (message) {
        window.setTimeout(function () {
            message.style.transition = "opacity 0.4s ease";
            message.style.opacity = "0";

            window.setTimeout(function () {
                message.style.display = "none";
            }, 400);
        }, 3500);
    });
});



// Check the due date when the project form is submitted.
document.addEventListener("DOMContentLoaded", function () {
    const dueDate = document.querySelector("#due-date");
    if (!dueDate) return;

    const projectForm = dueDate.closest("form");

    dueDate.addEventListener("input", function () {
        dueDate.setCustomValidity("");
    });

    projectForm.addEventListener("submit", function (event) {
        const value = dueDate.value.trim();
        dueDate.setCustomValidity("");

        if (!/^\d{2}-\d{2}-\d{4}$/.test(value)) {
            dueDate.setCustomValidity("Enter the due date as DD-MM-YYYY.");
        } else {
            const parts = value.split("-");
            const day = Number(parts[0]);
            const month = Number(parts[1]);
            const year = Number(parts[2]);
            const entered = new Date(year, month - 1, day);
            const validDate = entered.getFullYear() === year &&
                entered.getMonth() === month - 1 &&
                entered.getDate() === day;

            if (!validDate) {
                dueDate.setCustomValidity("Enter a valid date.");
            } else {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                entered.setHours(0, 0, 0, 0);

                if (entered < today) {
                    dueDate.setCustomValidity("Due date cannot be in the past.");
                }
            }
        }

        if (!dueDate.checkValidity()) {
            event.preventDefault();
            dueDate.reportValidity();
        }
    });
});
