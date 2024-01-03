function setTheme(theme = false) {
    var btn = document.getElementById("theme-btn");
    // set specific theme
    if (theme === 'dark') btn.innerHTML = '<i class="fa fa-lightbulb-o"></i>';
    else if (theme === 'light') btn.innerHTML = '<i class="fa fa-moon-o"></i>';
    if (theme && theme !== ':switch') {
        localStorage['theme'] = theme;
        document.documentElement.setAttribute("data-bs-theme", theme);
    }
    // alternate theme
    else if (theme === ':switch') {
        if (localStorage['theme'] === 'dark') setTheme('light');
        else if (localStorage['theme'] === 'light') setTheme('dark');
    }
    // set current theme
    else {
        if (typeof localStorage['theme'] === 'undefined') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                setTheme('dark');
            } else setTheme('light');
        } else {
            if (localStorage['theme'] === 'dark') setTheme('dark');
            else setTheme('light');
        }
    }
}
if (typeof localStorage['theme'] !== 'undefined') {
    let theme = localStorage['theme'];
    document.documentElement.setAttribute("data-bs-theme", theme);
}
if (typeof localStorage['theme'] === 'undefined') {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute("data-bs-theme", 'dark');
    }
}
document.addEventListener("DOMContentLoaded", function() {
    setTheme();
    var btn = document.getElementById("theme-btn");
    btn.addEventListener("click", function () {
        setTheme(':switch');
    });
});
