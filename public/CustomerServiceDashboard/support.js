(function () {
  var baseUrl = document.querySelector('meta[name="app-base-url"]').content,
    API = baseUrl + "/support-api",
    token = document.querySelector('meta[name="csrf-token"]').content,
    notice = document.getElementById("notice"),
    list = document.getElementById("concernList"),
    refundBody = document.getElementById("refundBody");
  function safe(v) {
    return String(v == null ? "" : v)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
  function show(m) {
    notice.textContent = m;
    notice.hidden = false;
    clearTimeout(show.t);
    show.t = setTimeout(function () {
      notice.hidden = true;
    }, 3000);
  }
  function api(resource, o) {
    o = o || {};
    var u = API + "?resource=" + resource + (o.id ? "&id=" + o.id : ""),
      c = { method: o.method || "GET", headers: { "X-CSRF-Token": token } };
    if (o.body) {
      c.headers["Content-Type"] = "application/json";
      c.body = JSON.stringify(o.body);
    }
    return fetch(u, c).then(function (r) {
      if (r.status === 401) {
        location = baseUrl + "/login";
        throw Error("Not authenticated");
      }
      return r.json().then(function (d) {
        if (!r.ok) throw Error(d.error || "Request failed");
        return d;
      });
    });
  }
  function summary() {
    api("summary").then(function (d) {
      ["open", "in_progress", "resolved", "pending_refunds"].forEach(
        function (k) {
          document.getElementById(k).textContent = d[k];
        },
      );
    });
  }
  function statuses(s) {
    return ["open", "in_progress", "resolved", "closed"]
      .map(function (v) {
        return (
          '<option value="' +
          v +
          '"' +
          (v === s ? " selected" : "") +
          ">" +
          safe(v.replace("_", " ")) +
          "</option>"
        );
      })
      .join("");
  }
  function concerns() {
    api("concerns").then(function (rows) {
      if (!rows.length) {
        list.innerHTML =
          '<div class="empty-state">No customer concerns yet.</div>';
        return;
      }
      list.innerHTML = rows
        .map(function (c) {
          var order = c.order_id
            ? "<p><strong>Order:</strong> #" +
              c.order_id +
              " · " +
              safe(c.order_status) +
              " · PHP " +
              Number(c.total_amount).toFixed(2) +
              "</p>"
            : "<p><strong>Order:</strong> Not linked</p>";
          return (
            '<article class="support-card"><h3>#' +
            c.concern_id +
            " · " +
            safe(c.subject) +
            '</h3><div class="support-meta"><p><strong>Customer:</strong> ' +
            safe(c.customer_name) +
            "</p><p><strong>Email:</strong> " +
            safe(c.email) +
            "</p><p><strong>Phone:</strong> " +
            safe(c.phone || "Not provided") +
            "</p>" +
            order +
            "<p><strong>Created:</strong> " +
            safe(c.created_at) +
            "</p></div><p>" +
            safe(c.message) +
            '</p><form class="support-form" data-concern="' +
            c.concern_id +
            '"><label>Status<select name="status">' +
            statuses(c.status) +
            '</select></label><label>Response<textarea name="response" required>' +
            safe(c.response) +
            '</textarea></label><button type="submit">Save Reply</button></form>' +
            (c.order_id
              ? '<form class="refund-form" data-refund="' +
                c.concern_id +
                '"><label>Refund reason<input name="reason" required></label><label>Customer Service notes<input name="notes"></label><button type="submit">Request Refund Review</button></form>'
              : "") +
            "</article>"
          );
        })
        .join("");
    });
  }
  function refunds() {
    api("refunds").then(function (rows) {
      refundBody.innerHTML = rows
        .map(function (r) {
          return (
            "<tr><td>#" +
            r.refund_request_id +
            "</td><td>#" +
            r.order_id +
            "</td><td>" +
            safe(r.reason) +
            "</td><td>" +
            safe(r.status) +
            "</td><td>" +
            safe(r.requested_at) +
            "</td></tr>"
          );
        })
        .join("");
    });
  }
  list.addEventListener("submit", function (e) {
    e.preventDefault();
    var f = e.target;
    if (f.dataset.concern) {
      api("concerns", {
        method: "PUT",
        id: f.dataset.concern,
        body: {
          status: f.elements.status.value,
          response: f.elements.response.value,
        },
      })
        .then(function () {
          show("Concern updated.");
          summary();
          concerns();
        })
        .catch(function (x) {
          show(x.message);
        });
    } else if (f.dataset.refund) {
      api("refunds", {
        method: "POST",
        id: f.dataset.refund,
        body: {
          reason: f.elements.reason.value,
          notes: f.elements.notes.value,
        },
      })
        .then(function () {
          show("Refund review requested.");
          summary();
          refunds();
        })
        .catch(function (x) {
          show(x.message);
        });
    }
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
  summary();
  concerns();
  refunds();
})();
