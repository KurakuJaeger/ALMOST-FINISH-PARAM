(function (app) {
  "use strict";

  var applicationBody = document.getElementById("applicationBody");
  var refundBody = document.getElementById("refundBody");
  var reportBody = document.getElementById("reportBody");
  var auditLog = document.getElementById("auditLog");

  function loadSummary() {
    return app.api("summary").then(function (data) {
      document.getElementById("totalProducts").textContent = data.total_products;
      document.getElementById("totalVariants").textContent = data.total_variants;
      document.getElementById("totalStock").textContent = data.total_stock;
      document.getElementById("lowStock").textContent = data.low_stock;
      document.getElementById("inventoryValue").textContent = "PHP " + Number(data.inventory_value).toFixed(2);
    }).catch(function () {
      // The rest of the dashboard can still work if its summary is unavailable.
    });
  }

  function loadApplications() {
    return app.api("applications").then(function (applications) {
      applicationBody.innerHTML = applications.map(function (item) {
        var actions = item.status === "pending"
          ? '<div class="row-actions"><button type="button" class="ghost-button" data-review="approved">Approve</button>' +
            '<button type="button" class="danger-button" data-review="rejected">Reject</button></div>'
          : app.safeText(app.titleCase(item.status));
        var details = item.reason || item.experience || item.availability || "-";

        return '<tr data-application-id="' + item.application_id + '">' +
          "<td>" + app.safeText(item.complete_name) + "</td>" +
          "<td>" + app.safeText(item.email) + "<br>" + app.safeText(item.phone) + "</td>" +
          "<td>" + app.safeText(item.requested_role) + "</td>" +
          "<td>" + app.safeText(details) + "</td><td>" + actions + "</td></tr>";
      }).join("");
    }).catch(function (error) {
      app.showNotice("Could not load applications: " + error);
    });
  }

  function refundActions(refund) {
    var status = String(refund.status || "pending");
    var content = '<span class="status-pill status-' + app.safeText(status) + '">' +
      app.safeText(app.titleCase(status)) + "</span>";

    if (status === "pending") {
      content += '<input class="refund-notes" placeholder="Admin notes (optional)" ' +
        'aria-label="Admin notes for refund request ' + refund.refund_request_id + '">' +
        '<div class="refund-actions"><button type="button" data-refund-action="approved">Approve</button>' +
        '<button type="button" class="danger-button" data-refund-action="rejected">Reject</button></div>';
    } else if (status === "approved") {
      content += '<div class="refund-actions"><button type="button" data-refund-action="execute">Mark Refunded</button></div>';
    }
    return content;
  }

  function loadRefunds() {
    return app.api("refunds").then(function (refunds) {
      if (!refunds.length) {
        refundBody.innerHTML = '<tr><td colspan="5">No refund requests have been submitted.</td></tr>';
        return;
      }

      refundBody.innerHTML = refunds.map(function (refund) {
        var serviceNote = refund.customer_service_notes
          ? "<br><small>CS: " + app.safeText(refund.customer_service_notes) + "</small>" : "";
        var adminNote = refund.admin_notes
          ? "<br><small>Admin: " + app.safeText(refund.admin_notes) + "</small>" : "";

        return '<tr data-refund-id="' + refund.refund_request_id + '">' +
          "<td>#" + refund.refund_request_id + "<br><small>" + app.safeText(refund.requested_at) + "</small></td>" +
          "<td>" + app.safeText(refund.customer_name) + "<br><small>" + app.safeText(refund.customer_email) + "</small></td>" +
          "<td>Order #" + refund.order_id + "<br>PHP " + Number(refund.total_amount).toFixed(2) + " - " +
          app.safeText(app.titleCase(refund.payment_method || "unrecorded")) + "</td>" +
          "<td>" + app.safeText(refund.reason) + serviceNote + adminNote + "</td>" +
          "<td>" + refundActions(refund) + "</td></tr>";
      }).join("");
    }).catch(function (error) {
      app.showNotice("Could not load refund requests: " + error);
    });
  }

  function loadReport() {
    return app.api("report").then(function (rows) {
      reportBody.innerHTML = rows.map(function (row) {
        return "<tr><td>" + app.safeText(row.product_name) + "</td>" +
          "<td>" + app.safeText(row.color) + "</td>" +
          "<td>" + app.safeText(row.size) + "</td>" +
          "<td>" + app.safeText(row.category_name) + "</td>" +
          "<td>" + row.stock_quantity + "</td>" +
          "<td>PHP " + Number(row.price).toFixed(2) + "</td>" +
          "<td>PHP " + Number(row.total_value).toFixed(2) + "</td></tr>";
      }).join("");
    }).catch(function (error) {
      app.showNotice("Could not load report: " + error);
    });
  }

  function loadAudit() {
    return app.api("audit").then(function (rows) {
      auditLog.innerHTML = rows.map(function (row) {
        return "<tr><td>" + app.safeText(row.created_at) + "</td>" +
          "<td>" + app.safeText(row.actor) + "</td>" +
          '<td><span class="audit-role">' + app.safeText(row.actor_role || "System") + "</span></td>" +
          "<td>" + app.safeText(row.details || row.action_name) + "</td></tr>";
      }).join("");
    }).catch(function (error) {
      app.showNotice("Could not load audit log: " + error);
    });
  }

  applicationBody.addEventListener("click", function (event) {
    var status = event.target.dataset.review;
    if (!status) return;
    var row = event.target.closest("[data-application-id]");
    app.api("applications", {
      method: "PUT",
      id: row.dataset.applicationId,
      body: { status: status },
    }).then(function () {
      app.showNotice("Application " + status + ".");
      app.refresh("applications");
      app.refresh("audit");
    }).catch(function (error) {
      app.showNotice("Could not review application: " + error);
    });
  });

  refundBody.addEventListener("click", function (event) {
    var action = event.target.dataset.refundAction;
    if (!action) return;
    var row = event.target.closest("[data-refund-id]");
    var notes = row.querySelector(".refund-notes");
    var request = action === "execute"
      ? app.api("refunds", { method: "POST", id: row.dataset.refundId })
      : app.api("refunds", {
          method: "PUT",
          id: row.dataset.refundId,
          body: { decision: action, notes: notes ? notes.value : "" },
        });

    event.target.disabled = true;
    request.then(function () {
      var message = action === "execute"
        ? "Refund marked as manually completed."
        : "Refund request " + action + ".";
      app.showNotice(message);
      app.refresh("refunds");
      app.refresh("audit");
    }).catch(function (error) {
      app.showNotice("Could not update refund: " + error);
      event.target.disabled = false;
    });
  });

  app.registerLoader("summary", loadSummary);
  app.registerLoader("applications", loadApplications);
  app.registerLoader("refunds", loadRefunds);
  app.registerLoader("report", loadReport);
  app.registerLoader("audit", loadAudit);
})(window.ParamAdmin);
