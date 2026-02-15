logged_in = false;
host = window.location.protocol + "//" + window.location.host;

async function loadPage() {
  console.log(host);
  try {
    const response = await fetch(host + "/user/refresh/refresh_token");
    if (response.status === 200) {
      response = await fetch(host + "/user/refresh/access_token");
      if (response.status === 200) {
        logged_in = true;
      }
    }
  } catch { }

  if (logged_in === false) {
    try {
      const response = await fetch(host + "/resource/login.html");
      document.getElementById("page_content").innerHTML = await response.text();
    } catch { }
  }
}
