document.addEventListener('DOMContentLoaded', function () {
    const dropdownBtn = document.querySelector('.dropbtn');
    const dropdownContent = document.querySelector('.dropdown-content');

    dropdownBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdownContent.classList.toggle('show');
    });

    window.addEventListener('click', function () {
        if (dropdownContent.classList.contains('show')) {
            dropdownContent.classList.remove('show');
        }
    });
});

function showFeature(feature) {
    // Hide all content sections
    const contents = document.querySelectorAll('.feature-content');
    contents.forEach(content => content.style.display = 'none');

    // Show the selected feature's content
    document.getElementById(`${feature}-content`).style.display = 'block';

    // Load data based on feature type
    if (feature === 'availability') {
        fetchData('inventory', '#availability-content .content-list');
    }
}

function fetchData(type, selector) {
    fetch(`staff_dashboard.php?type=${type}`)
        .then(response => response.json())
        .then(data => {
            let content = document.querySelector(selector);
            content.innerHTML = '<ul>';
            if (data.error) {
                content.innerHTML += `<li>${data.error}</li>`;
            } else {
                data.forEach(item => {
                    content.innerHTML += `<li>Item ID: ${item.item_id}, Name: ${item.name}, SKU: ${item.sku}, Description: ${item.description}, Quantity: ${item.quantity}</li>`;
                });
            }
            content.innerHTML += '</ul>';
        })
        .catch(error => console.error('Error:', error));
}

function logout() {
    window.location.href = '../auth/logout.php';
}
