// ================= إدارة الوضع الداكن (Dark Mode) =================
const themeToggleBtn = document.getElementById('theme-toggle');
const currentTheme = localStorage.getItem('theme') || 'light';

// تطبيق الثيم المحفوظ
if (currentTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
}

themeToggleBtn.addEventListener('click', () => {
    let theme = document.documentElement.getAttribute('data-theme');
    if (theme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
});

// ================= فلترة الأخبار ديناميكياً (Category Filters) =================
const dummyNews = [
    { title: "تطورات التكنولوجيا في 2026", category: "tech", img: "https://via.placeholder.com/300x150" },
    { title: "قرارات محلية جديدة", category: "local", img: "https://via.placeholder.com/300x150" },
    { title: "دراسة: تأثير الذكاء الاصطناعي", category: "studies", img: "https://via.placeholder.com/300x150" },
    { title: "Global Markets Update", category: "english", img: "https://via.placeholder.com/300x150" },
    { title: "تحليل سياسي للوضع الراهن", category: "politics", img: "https://via.placeholder.com/300x150" }
];

const newsGrid = document.getElementById('news-grid');

function displayNews(newsArray) {
    newsGrid.innerHTML = '';
    newsArray.forEach(news => {
        newsGrid.innerHTML += `
            <div class="news-card">
                <img src="${news.img}" alt="${news.title}">
                <div class="news-card-body">
                    <span class="badge">${news.category}</span>
                    <h4>${news.title}</h4>
                </div>
            </div>
        `;
    });
}

// عرض جميع الأخبار عند تحميل الصفحة
displayNews(dummyNews);

function filterNews(category) {
    if (category === 'all') {
        displayNews(dummyNews);
    } else {
        const filtered = dummyNews.filter(news => news.category === category);
        displayNews(filtered);
    }
}

// ================= إدارة تسجيل الدخول ولوحة التحكم =================
const publicSite = document.getElementById('public-site');
const loginPortal = document.getElementById('login-portal');
const adminDashboard = document.getElementById('admin-dashboard');
let currentUserRole = 'guest'; // الأدوار: admin, editor, guest

function showLogin() {
    publicSite.classList.add('hidden');
    loginPortal.classList.remove('hidden');
}

function hideLogin() {
    loginPortal.classList.add('hidden');
    publicSite.classList.remove('hidden');
}

// تسجيل الدخول (بدون CAPTCHA كطلبك)
document.getElementById('login-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const user = document.getElementById('username').value;
    
    // محاكاة تسجيل الدخول (في الواقع يتم عبر Backend)
    if(user === "admin") {
        currentUserRole = 'admin';
    } else {
        currentUserRole = 'editor';
    }

    loginPortal.classList.add('hidden');
    adminDashboard.classList.remove('hidden');
    document.getElementById('admin-user-name').innerText = `مرحباً بك (${user})`;
    
    // إخفاء إدارة الأعضاء إذا لم يكن المدير
    if(currentUserRole !== 'admin') {
        document.querySelector('.admin-sidebar ul li:nth-child(2)').style.display = 'none';
    } else {
        document.querySelector('.admin-sidebar ul li:nth-child(2)').style.display = 'block';
    }
});

function logout() {
    adminDashboard.classList.add('hidden');
    publicSite.classList.remove('hidden');
    document.getElementById('login-form').reset();
    currentUserRole = 'guest';
}

function switchAdminTab(tabId) {
    // إخفاء جميع الأقسام
    document.getElementById('manage-posts').classList.add('hidden');
    document.getElementById('manage-users').classList.add('hidden');
    document.getElementById('admin-settings').classList.add('hidden');
    
    // إظهار القسم المطلوب فقط
    document.getElementById(tabId).classList.remove('hidden');
}
