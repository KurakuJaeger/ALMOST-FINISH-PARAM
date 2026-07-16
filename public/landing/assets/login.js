document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('.password-toggle');
  const password = document.getElementById('login-password');

  if (!toggle || !password) return;

  toggle.addEventListener('click', function () {
    const isVisible = password.type === 'text';
    password.type = isVisible ? 'password' : 'text';
    toggle.textContent = isVisible ? 'Show' : 'Hide';
    toggle.setAttribute('aria-pressed', String(!isVisible));
  });
});
