let currentEditId = null;
let isLoading = false;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('articleForm').addEventListener('submit', handleFormSubmit);
});

function handleFormSubmit(e) {
    e.preventDefault();
    if (isLoading) return;

    const submitBtn = e.target.querySelector('button[type="submit"]');
    showLoader(submitBtn);

    const formData = new FormData(e.target);
    const data = {
        title: formData.get('title'),
        content: formData.get('content')
    };

    if (currentEditId) {
        updateArticle(currentEditId, data, submitBtn);
    } else {
        createArticle(data, submitBtn);
    }
}

function createArticle(data, button) {
    showLoader(button);

    fetch('/admin/news', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': getCsrfToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast('success', 'Article created successfully!');
                resetForm();
                loadAllNews();
            } else {
                showToast('error', result.error || 'Error creating article');
            }
        })
        .catch(err => console.error('Error:', err))
        .finally(() => hideLoader(button));
}

function editArticle(id) {
    if (isLoading) return;
    showLoader();

    fetch(`/api/news/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentEditId = id;
                document.getElementById('formTitle').textContent = 'Edit Article';
                document.getElementById('formButton').textContent = 'Edit';
                document.getElementById('articleId').value = data.data.id;
                document.getElementById('title').value = data.data.title;
                document.getElementById('content').value = data.data.content;

                document.getElementById('cancelEditBtn').style.display = 'inline-flex';

                document.getElementById('newsForm').scrollIntoView({behavior: 'smooth', block: 'start'});
            } else {
                showToast('error', data.error || 'Error loading article');
            }
        })
        .catch(err => console.error('Error:', err))
        .finally(() => hideLoader());
}

function updateArticle(id, data, button) {
    showLoader(button);

    fetch(`/admin/news/${id}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-Token': getCsrfToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast('success', 'Article updated successfully!');
                resetForm();
                loadAllNews();
            } else {
                showToast('error', result.error || 'Error updating article');
            }
        })
        .catch(err => console.error('Error:', err))
        .finally(() => hideLoader(button));
}

function deleteArticle(id) {
    if (isLoading) return;

    if (!confirm('Are you sure you want to delete this article?')) return;

    fetch(`/admin/news/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-Token': getCsrfToken(),
            'Content-Type': 'application/json'
        }
    })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast('success', 'Article deleted successfully');
                loadAllNews();
            } else {
                showToast('error', result.error || 'Error deleting article');
            }
        })
        .catch(err => console.error('Error:', err))
        .finally(() => hideLoader());
}

function loadAllNews() {
    showLoader();

    fetch('/api/news')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateNewsList(data.data);
            } else {
                showToast('error', data.error || 'Error loading news');
            }
        })
        .catch(err => console.error('Error:', err))
        .finally(() => hideLoader());
}

function updateNewsList(news) {
    const newsContainer = document.querySelector('.news-items');
    const emptyState = document.querySelector('.empty-state');

    if (!news || news.length === 0) {
        newsContainer.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }

    emptyState.style.display = 'none';

    newsContainer.innerHTML = news.map(article => `
        <div class="news-item" data-id="${article.id}">
            <div class="news-item-content">
                <div class="news-title truncate-one-line">${article.title}</div>
                <p class="truncate-one-line">${article.content}</p>
            </div>
            <div class="news-item-actions">
                <button class="btn-icon" onclick="editArticle(${article.id})" title="Edit">
                    <img src="/assets/images/icons/pencil.svg" alt="Edit"/>
                </button>
                <button class="btn-icon" onclick="deleteArticle(${article.id})" title="Delete">
                    <img src="/assets/images/icons/close.svg" alt="Delete"/>
                </button>
            </div>
        </div>
    `).join('');
}

function resetForm() {
    currentEditId = null;
    document.getElementById('formTitle').textContent = 'Create New Article';
    document.getElementById('formButton').textContent = 'Create';
    document.getElementById('articleId').value = '';
    document.getElementById('title').value = '';
    document.getElementById('content').value = '';
    document.getElementById('cancelEditBtn').style.display = 'none';
}

function cancelEdit() {
    resetForm();
}
