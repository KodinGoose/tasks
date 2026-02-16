let logged_in = false;
let access_token_interval;
let host = window.location.protocol + "//" + window.location.host;

async function loadPage() {
  let response = await fetch(host + "/user/refresh/refresh_token");
  if (response.status === 200) {
    response = await fetch(host + "/user/refresh/access_token");
    if (response.status === 200) {
      logged_in = true;
    }
    access_token_interval = setInterval(function() { refreshAccessToken(); }, 60000);
  }

  refreshPage();
}

async function refreshPage() {
  if (logged_in === false) {
    response = await fetch(host + "/resource/login.html");
    document.getElementById("page_content").innerHTML = await response.text();
  } else {
    response = await fetch(host + "/resource/tasks.html");
    document.getElementById("page_content").innerHTML = await response.text();
  }
}

async function login() {
  let username = validateString("login_username", 255, 1);
  let password = validatePassword("login_password");

  let errored = false;
  if (username === false) {
    errored = true;
    document.getElementById("login_username_error").innerHTML = "Username cannot be empty and cannot be longer than 255 characters";
    document.getElementById("login_username_error").hidden = false;
  } else {
    document.getElementById("login_username_error").innerHTML = "";
    document.getElementById("login_username_error").hidden = true;
  }
  if (password === false) {
    errored = true;
    document.getElementById("login_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("login_password_error").hidden = false;
  } else {
    document.getElementById("login_password_error").innerHTML = "";
    document.getElementById("login_password_error").hidden = true;
  }
  if (errored) return;

  let response = await fetch(host + "/user/login", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ username: username, password: password }),
  });

  if (response.status !== 200) {
    let res_body = await response.text();
    if (response.status === 403 && res_body === "Unauthorised") {
      alert("Incorrect username or password");
      return;
    } else {
      alert(res_body);
      return;
    }
  }

  logged_in = true;

  access_token_interval = setInterval(function() { refreshAccessToken(); }, 60000);

  refreshPage();
}

async function register() {
  let username = validateString("register_username", 255, 1);
  let password = validatePassword("register_password");
  let repeat_password = validatePassword("register_repeat_password");

  let errored = false;
  if (username === false) {
    errored = true;
    document.getElementById("register_username_error").innerHTML = "Username cannot be empty and cannot be longer than 255 characters";
    document.getElementById("register_username_error").hidden = false;
  } else {
    document.getElementById("register_username_error").innerHTML = "";
    document.getElementById("register_username_error").hidden = true;
  }
  if (password === false) {
    errored = true;
    document.getElementById("register_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("register_password_error").hidden = false;
  } else {
    document.getElementById("register_password_error").innerHTML = "";
    document.getElementById("register_password_error").hidden = true;
  }
  if (repeat_password === false) {
    errored = true;
    document.getElementById("register_repeat_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("register_repeat_password_error").hidden = false;
  } else {
    document.getElementById("register_repeat_password_error").innerHTML = "";
    document.getElementById("register_repeat_password_error").hidden = true;
  }
  if (errored) return;

  if (password !== repeat_password) {
    document.getElementById("register_repeat_password_error").innerHTML = "Passwords do not match";
    document.getElementById("register_repeat_password_error").hidden = false;
    return;
  } else {
    document.getElementById("register_repeat_password_error").innerHTML = "";
    document.getElementById("register_repeat_password_error").hidden = true;
  }

  const response = await fetch(host + "/user/register", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ username: username, password: password }),
  });

  if (response.status !== 201) {
    alert(await response.text());
    return;
  }

  alert("Successful registration")
}

function validateString(id, max_chars, min_chars) {
  let string = document.getElementById(id).value;
  if (string.length < min_chars || string.length > max_chars) {
    return false;
  }
  return string;
}

function validatePassword(id) {
  let password = validateString(id, 255, 12);
  if (password === false) return false;
  return password;
}

async function refreshAccessToken() {
  response = await fetch(host + "/user/refresh/access_token");
  if (response.status === 200) {
    logged_in = true;
  }
}

async function logout() {
  clearInterval(access_token_interval);
  response = await fetch(host + "/user/logout");
  if (response.status === 204) {
    logged_in = false;
  } else {
    alert(await response.text());
  }

  refreshPage();
}

async function del () {
  let password = validatePassword("delete_password");
  if (password === false) {
    document.getElementById("delete_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("delete_password_error").hidden = false;
    return;
  } else {
    document.getElementById("delete_password_error").innerHTML = "";
    document.getElementById("delete_password_error").hidden = true;
  }

  clearInterval(access_token_interval);
  response = await fetch(host + "/user/delete", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ password: password }),
  });
  if (response.status === 204) {
    logged_in = false;
  } else {
    alert(await response.text());
  }

  refreshPage();
}
