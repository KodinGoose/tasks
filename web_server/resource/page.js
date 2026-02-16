let access_token_interval;
let host = window.location.protocol + "//" + window.location.host;

function getCookie(cookieName) {
  const cookies = document.cookie.split('; ');
  for (const cookie of cookies) {
    const [name, value] = cookie.split('=');
    if (name === cookieName) {
      return decodeURIComponent(value);
    }
  }
  return null;
}

async function loadPage() {
  let response = await fetch(host + "/user/refresh/refresh_token");
  if (response.status === 200) {
    response = await fetch(host + "/user/refresh/access_token");
    if (response.status === 200) {
      if (getCookie("State") === "logged_out" || getCookie("State") === null) {
        document.cookie = "State=logged_in";
      }
    }
    access_token_interval = setInterval(function() { refreshAccessToken(); }, 60000);
  } else {
    document.cookie = "State=logged_out";
  }

  refreshPage();
}

async function refreshPage() {
  let state = getCookie("State");
  if (state === "logged_out" || state === null) {
    response = await fetch(host + "/resource/login.html");
    document.getElementById("page_content").innerHTML = await response.text();
  } else if (state === "logged_in") {
    response = await fetch(host + "/resource/tasks.html");
    document.getElementById("page_content").innerHTML = await response.text();
    loadTasks();
  } else if (state === "changing_password") {
    response = await fetch(host + "/resource/change_pass.html");
    document.getElementById("page_content").innerHTML = await response.text();
  } else if (state === "delete_profile") {
    response = await fetch(host + "/resource/delete_profile.html");
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

  document.cookie = "State=logged_in";

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
}

async function logout() {
  clearInterval(access_token_interval);
  response = await fetch(host + "/user/logout");
  if (response.status === 204) {
    clearInterval(access_token_interval);
    document.cookie = "State=logged_out";
    refreshPage();
  } else {
    alert(await response.text());
    return;
  }
}

async function del() {
  let password = validatePassword("delete_password");
  if (password === false) {
    document.getElementById("delete_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("delete_password_error").hidden = false;
    return;
  } else {
    document.getElementById("delete_password_error").innerHTML = "";
    document.getElementById("delete_password_error").hidden = true;
  }

  response = await fetch(host + "/user/delete", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ password: password }),
  });
  if (response.status === 204) {
    document.cookie = "State=logged_out";
  } else {
    res_body = await response.text();
    if (response.status === 403 && res_body === "Unauthorised") {
      alert("Incorrect username or password");
    } else {
      alert(res_body);
    }
    return;
  }

  clearInterval(access_token_interval);
  refreshPage();
}

async function modPassword() {
  let old_password = validatePassword("mod_old_password");
  let new_password = validatePassword("mod_new_password");
  let errored = false;
  if (old_password === false) {
    document.getElementById("mod_old_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("mod_old_password_error").hidden = false;
    errored = true;
  } else {
    document.getElementById("mod_old_password_error").innerHTML = "";
    document.getElementById("mod_old_password_error").hidden = true;
  }
  if (new_password === false) {
    document.getElementById("mod_new_password_error").innerHTML = "Password length must be at least 12 characters and cannot be longer than 255 characters";
    document.getElementById("mod_new_password_error").hidden = false;
    errored = true;
  } else {
    document.getElementById("mod_new_password_error").innerHTML = "";
    document.getElementById("mod_new_password_error").hidden = true;
  }
  if (errored === true) return;

  response = await fetch(host + "/user/mod_password", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ old_password: old_password, password: new_password }),
  });
  if (response.status !== 204) {
    res_body = await response.text();
    if (response.status === 403 && res_body === "Unauthorised") {
      alert("Incorrect username or password");
    } else {
      alert(res_body);
    }
    return;
  }


  clearInterval(access_token_interval);
  document.cookie = "State=logged_out";
  refreshPage();
}

async function newTask() {
  let title = validateString("new_task_title", 255, 1);
  if (title === false) {
    document.getElementById("new_task_title_error").innerHTML = "Title cannot be empty and cannot be longer than 255 characters";
    document.getElementById("new_task_title_error").hidden = false;
    return;
  } else {
    document.getElementById("new_task_title_error").innerHTML = "";
    document.getElementById("new_task_title_error").hidden = true;
  }

  response = await fetch(host + "/tasks/new", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ title: title }),
  });

  if (response.status !== 201) {
    alert(await (response.text()));
  } else {
    refreshPage();
  }
}

async function loadTasks() {
  let response = await fetch(host + "/tasks/all");
  if (response.status !== 200) {
    alert("Couldn't load your shit");
    return;
  }
  let tasks = await response.json();

  for (let i = 0; i < tasks.length; i++) {
    let str = "<tr><td>";
    str += "<input id=\"task_" + tasks[i].id + "_checkbox\" class=\"checkbox\" type=\"checkbox\" value=\"" + tasks[i].done + "\" onclick=\"modTask(" + tasks[i].id + ")\">"
    str += "</td><td>";
    str += "<input id=\"task_" + tasks[i].id + "_title\" class=\"title\" type=\"text\" value=\"" + tasks[i].title + "\"onfocusout=\"modTask(" + tasks[i].id + ")\">"
    str += "</td><td>";
    str += "<button class=\"delete_button\" onclick=\"delTask(" + tasks[i].id + ")\">Delete task</button>"
    str += "</td></tr>";
    document.getElementById("your_shit").innerHTML += str;
  }
}

async function modTask(id) {
  let done = document.getElementById("task_" + id + "_checkbox").checked;
  let title = document.getElementById("task_" + id + "_title").value;
  response = await fetch(host + "/tasks/mod", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: id, done: done, title: title }),
  });
  if (response.status !== 204) {
    alert(await response.text());
    return;
  }
}

async function delTask(id) {
  response = await fetch(host + "/tasks/del", {
    method: "DELETE",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: id }),
  });
  if (response.status !== 200) {
    alert(await response.text());
    return;
  }

  refreshPage();
}
