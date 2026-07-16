(function (app) {
  "use strict";

  app.setupNavigation();
  app.showSection("dashboard");
  ["summary", "users", "inventory", "applications", "refunds", "report", "audit"]
    .forEach(function (section) {
      app.refresh(section);
    });
})(window.ParamAdmin);
