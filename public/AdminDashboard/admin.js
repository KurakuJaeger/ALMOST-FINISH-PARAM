(function () {
  "use strict";

  var baseUrl = document.querySelector('meta[name="app-base-url"]').content;
  var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
  var notice = document.getElementById("notice");
  var loaders = {};

  function safeText(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showNotice(message, persistent) {
    notice.textContent = message;
    notice.hidden = false;
    window.clearTimeout(showNotice.timer);
    if (!persistent) {
      showNotice.timer = window.setTimeout(function () {
        notice.hidden = true;
      }, 2500);
    }
  }

  function titleCase(value) {
    value = String(value || "");
    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
  }

  function api(resource, options) {
    options = options || {};
    var url = baseUrl + "/api?resource=" + encodeURIComponent(resource);
    if (options.id) url += "&id=" + encodeURIComponent(options.id);

    var request = {
      method: options.method || "GET",
      headers: { "X-CSRF-Token": csrfToken },
    };
    if (options.body) {
      request.headers["Content-Type"] = "application/json";
      request.body = JSON.stringify(options.body);
    } else if (options.formData) {
      request.body = options.formData;
    }

    return fetch(url, request).then(function (response) {
      if (response.status === 401) {
        window.location = baseUrl + "/login";
        return Promise.reject("Not authenticated");
      }
      return response.json().then(function (data) {
        if (!response.ok) return Promise.reject(data.error || "Request failed");
        return data;
      });
    });
  }

  function registerLoader(name, loader) {
    loaders[name] = loader;
  }

  function refresh(name) {
    if (loaders[name]) return loaders[name]();
    return Promise.resolve();
  }

  function showSection(sectionId) {
    document.querySelectorAll(".page-section").forEach(function (section) {
      section.classList.remove("active");
    });
    var target = document.getElementById(sectionId);
    if (target) target.classList.add("active");
  }

  function setupNavigation() {
    document.querySelectorAll(".side-nav a, .top-nav a").forEach(function (link) {
      link.addEventListener("click", function (event) {
        var destination = link.getAttribute("href");
        if (!destination || destination.charAt(0) !== "#") return;
        event.preventDefault();
        showSection(destination.substring(1));
      });
    });
  }

  window.ParamAdmin = {
    api: api,
    baseUrl: baseUrl,
    refresh: refresh,
    registerLoader: registerLoader,
    safeText: safeText,
    setupNavigation: setupNavigation,
    showNotice: showNotice,
    showSection: showSection,
    titleCase: titleCase,
  };
})();
