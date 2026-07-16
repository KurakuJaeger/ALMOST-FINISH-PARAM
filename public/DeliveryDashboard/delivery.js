(function () {
  var baseUrl = document.querySelector('meta[name="app-base-url"]').content;
  var API = baseUrl + "/delivery-api";
  var token = document.querySelector('meta[name="csrf-token"]').content;
  var notice = document.getElementById("notice");
  var list = document.getElementById("deliveryList");
  function safe(v) {
    return String(v == null ? "" : v)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
  function show(message) {
    notice.textContent = message;
    notice.hidden = false;
    clearTimeout(show.timer);
    show.timer = setTimeout(function () {
      notice.hidden = true;
    }, 3000);
  }
  function api(resource, options) {
    options = options || {};
    var url =
      API + "?resource=" + resource + (options.id ? "&id=" + options.id : "");
    var config = {
      method: options.method || "GET",
      headers: { "X-CSRF-Token": token },
    };
    if (options.body) {
      config.headers["Content-Type"] = "application/json";
      config.body = JSON.stringify(options.body);
    }
    return fetch(url, config).then(function (r) {
      if (r.status === 401) {
        location = baseUrl + "/login";
        throw new Error("Not authenticated");
      }
      return r.json().then(function (d) {
        if (!r.ok) throw new Error(d.error || "Request failed");
        return d;
      });
    });
  }
  function loadSummary() {
    api("summary").then(function (d) {
      ["total", "active", "delivered", "failed"].forEach(function (k) {
        document.getElementById(k).textContent = d[k];
      });
    });
  }
  function options(selected) {
    return [
      "pending",
      "assigned",
      "picked_up",
      "in_transit",
      "delivered",
      "failed",
    ]
      .map(function (s) {
        return (
          '<option value="' +
          s +
          '"' +
          (s === selected ? " selected" : "") +
          ">" +
          safe(s.replace("_", " ")) +
          "</option>"
        );
      })
      .join("");
  }
  function loadDeliveries() {
    api("deliveries").then(function (rows) {
      if (!rows.length) {
        list.innerHTML =
          '<div class="empty-state">There are no assigned or available deliveries right now.</div>';
        return;
      }
      list.innerHTML = rows
        .map(function (d) {
          var isAvailable = !d.assigned_to_user_id;
          var action = isAvailable
            ? '<div class="claim-panel"><span>Available delivery</span><button class="claim-button" type="button" data-id="' +
              d.delivery_id +
              '">Claim Delivery</button></div>'
            : '<form class="delivery-form" data-id="' +
              d.delivery_id +
              '"><label>Status<select name="status">' +
              options(d.delivery_status) +
              '</select></label><label>Delivery notes<textarea name="notes">' +
              safe(d.delivery_notes) +
              '</textarea></label><label>Proof image path<input name="proof" value="' +
              safe(d.proof_image_path) +
              '" placeholder="e.g. uploads/proof-123.jpg"></label><button type="submit">Save Update</button></form>';
          return (
            '<article class="delivery-card"><h3>Delivery #' +
            d.delivery_id +
            " · Order #" +
            d.order_id +
            '</h3><div class="delivery-meta"><p><strong>Customer:</strong> ' +
            safe(d.customer_name) +
            "</p><p><strong>Masked phone:</strong> " +
            safe(d.masked_phone_number || "Not provided") +
            "</p><p><strong>Address:</strong> " +
            safe(d.delivery_address_snapshot) +
            "</p><p><strong>Assigned:</strong> " +
            safe(d.assigned_at || "Waiting to be claimed") +
            "</p></div>" +
            action +
            "</article>"
          );
        })
        .join("");
    });
  }
  list.addEventListener("submit", function (e) {
    if (!e.target.classList.contains("delivery-form")) return;
    e.preventDefault();
    var f = e.target;
    api("deliveries", {
      method: "PUT",
      id: f.dataset.id,
      body: {
        status: f.elements.status.value,
        notes: f.elements.notes.value,
        proof: f.elements.proof.value,
      },
    })
      .then(function () {
        show("Delivery updated.");
        loadSummary();
        loadDeliveries();
      })
      .catch(function (err) {
        show(err.message);
      });
  });
  list.addEventListener("click", function (e) {
    if (!e.target.classList.contains("claim-button")) return;
    e.target.disabled = true;
    api("deliveries", { method: "POST", id: e.target.dataset.id })
      .then(function () {
        show("Delivery claimed and added to your assignments.");
        loadSummary();
        loadDeliveries();
      })
      .catch(function (err) {
        show(err.message);
        loadDeliveries();
      });
  });
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener("click", function (e) {
      e.preventDefault();
      document.querySelectorAll(".page-section").forEach(function (s) {
        s.classList.remove("active");
      });
      document.querySelector(a.getAttribute("href")).classList.add("active");
    });
  });
  loadSummary();
  loadDeliveries();
})();
