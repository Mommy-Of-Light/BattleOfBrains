const apiUrl = "http://localhost/dev-wsl/BattleOfBrains/api/";

async function apiRequest(endpoint, method = "GET", data = null) {
    const options = {
        method,
        headers: {
            "Content-Type": "application/json"
        }
    };

    if (data) {
        options.body = JSON.stringify(data);
        console.log("Request body:", options.body);
    }

    const response = await fetch(`${apiUrl}/${endpoint}`, options);
    console.log(`API Request: ${method} ${endpoint}`, data ? `with data: ${JSON.stringify(data)}` : "without data");
    console.log("API Response status:", response.status, "Response body:", await response.clone().text());

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || "API request failed");
    }

    return response.json();
}

document.addEventListener("DOMContentLoaded", () => {
    if (!localStorage.getItem("userId") && !window.location.href.endsWith("login.html") && !window.location.href.endsWith("register.html")) {
        window.location.href = "pages/login.html";
    } else if (!localStorage.getItem("userId") && window.location.href.endsWith("login.html")) {
        const loginForm = document.getElementById("loginForm");
        const canselButton = document.getElementById("cancel");
        canselButton.addEventListener("click", () => {
            window.location.href = "../index.html";
        });
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;

            console.log("Login attempt with username:", username);
            console.log("Login attempt with password:", password);

            try {
                const response = await apiRequest("users/", "POST", { username, password });
                localStorage.setItem("userId", response.user.id);
                window.location.href = "../index.html";
            } catch (error) {
                alert(error.message);
            }
        });
    } else if (localStorage.getItem("userId") && window.location.href.endsWith("login.html")) {
        window.location.href = "../index.html";
    }

    if (window.location.href.endsWith("register.html")) {
        const registerForm = document.getElementById("registerForm");
        const canselButton = document.getElementById("cancel");
        canselButton.addEventListener("click", () => {
            window.location.href = "../index.html";
        });
        registerForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;

            try {
                const response = await apiRequest("users/", "PUT", { username, password });
                window.location.href = "../index.html";
            } catch (error) {
                alert(error.message);
            }
        });
    }

    if (window.location.href.endsWith("index.html")) {
        const logoutButton = document.getElementById("logout");
        logoutButton.addEventListener("click", () => {
            localStorage.removeItem("userId");
            window.location.href = "pages/login.html";
        });
    }
});