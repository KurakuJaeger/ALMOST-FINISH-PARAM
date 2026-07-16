(function (app) {
  "use strict";

  var addUserForm = document.getElementById("addUserForm");
  var roleSelect = document.getElementById("newUserRole");
  var userList = document.getElementById("userList");
  var roles = [];

  function roleOptions(selectedRole) {
    return roles.map(function (role) {
      var selected = role.role_name === selectedRole ? " selected" : "";
      return "<option" + selected + ">" + app.safeText(role.role_name) + "</option>";
    }).join("");
  }

  function statusOptions(selectedStatus) {
    return ["Active", "Inactive"].map(function (status) {
      var selected = status === selectedStatus ? " selected" : "";
      return "<option" + selected + ">" + status + "</option>";
    }).join("");
  }

  function buildUserRow(user) {
    var row = document.createElement("form");
    var fullName = (user.first_name + " " + user.last_name).trim();
    row.className = "edit-row user-row";
    row.dataset.userId = user.user_id;
    row.innerHTML =
      '<label><span>Name</span><input name="name" value="' + app.safeText(fullName) + '" required></label>' +
      '<label><span>Email</span><input type="email" name="email" value="' + app.safeText(user.email) + '" required></label>' +
      '<label><span>Role</span><select name="role">' + roleOptions(user.role_name) + "</select></label>" +
      '<label><span>Status</span><select name="status">' + statusOptions(app.titleCase(user.status)) + "</select></label>" +
      '<div class="row-actions"><button type="submit" class="ghost-button">Update</button>' +
      '<button type="button" class="danger-button delete-user">Delete</button></div>';
    return row;
  }

  function loadUsers() {
    return app.api("roles").then(function (data) {
      roles = data;
      roleSelect.innerHTML = roleOptions(null);
      return app.api("users");
    }).then(function (users) {
      userList.querySelectorAll(".user-row").forEach(function (row) {
        row.remove();
      });
      users.forEach(function (user) {
        userList.appendChild(buildUserRow(user));
      });
    }).catch(function (error) {
      app.showNotice("Could not load users: " + error);
    });
  }

  addUserForm.addEventListener("submit", function (event) {
    event.preventDefault();
    var form = event.currentTarget;
    var details = {
      name: form.elements.name.value.trim(),
      email: form.elements.email.value.trim(),
      role: form.elements.role.value,
      status: form.elements.status.value,
    };

    if (!details.name || !details.email) {
      app.showNotice("Please complete the admin user fields.");
      return;
    }

    app.api("users", { method: "POST", body: details }).then(function (result) {
      var message = result.email_sent
        ? "User added. Account setup email sent."
        : "User added, but email was not sent (" + result.email_error + "). Setup link: " + result.setup_url;
      app.showNotice(message, !result.email_sent);
      form.reset();
      app.refresh("users");
      app.refresh("audit");
    }).catch(function (error) {
      app.showNotice("Could not add user: " + error);
    });
  });

  userList.addEventListener("submit", function (event) {
    if (!event.target.classList.contains("user-row")) return;
    event.preventDefault();
    var row = event.target;
    var details = {
      name: row.elements.name.value.trim(),
      email: row.elements.email.value.trim(),
      role: row.elements.role.value,
      status: row.elements.status.value,
    };
    app.api("users", { method: "PUT", id: row.dataset.userId, body: details }).then(function () {
      app.showNotice("Admin user updated.");
      app.refresh("audit");
    }).catch(function (error) {
      app.showNotice("Could not update user: " + error);
    });
  });

  userList.addEventListener("click", function (event) {
    if (!event.target.classList.contains("delete-user")) return;
    var row = event.target.closest(".user-row");
    app.api("users", { method: "DELETE", id: row.dataset.userId }).then(function () {
      row.remove();
      app.showNotice("Admin user deleted.");
      app.refresh("audit");
    }).catch(function (error) {
      app.showNotice("Could not delete user: " + error);
    });
  });

  app.registerLoader("users", loadUsers);
})(window.ParamAdmin);
