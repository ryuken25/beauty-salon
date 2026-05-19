#!/bin/bash
# E2E test script for auth + role flow. Uses curl with cookie jars to
# simulate a real browser. Run after `php spark serve --port 8088`.

set -uo pipefail

BASE="${BASE_URL:-http://localhost:8080}"
COOKIE_DIR="$(mktemp -d)"
trap 'rm -rf "$COOKIE_DIR"' EXIT

# Pretty print helpers
PASS="\033[32m✓ PASS\033[0m"
FAIL="\033[31m✗ FAIL\033[0m"
INFO="\033[36mℹ\033[0m"
fail_count=0

assert_eq() {
    local label="$1" expected="$2" actual="$3"
    if [ "$expected" = "$actual" ]; then
        echo -e "  $PASS  $label  ($actual)"
    else
        echo -e "  $FAIL  $label  expected=$expected actual=$actual"
        fail_count=$((fail_count + 1))
    fi
}

# ── Helper: extract Location header from a 302 response ────────────
get_redirect() {
    local jar="$1" url="$2" method="${3:-GET}" data="${4:-}"
    if [ "$method" = "POST" ]; then
        curl -s -o /dev/null -D - -b "$jar" -c "$jar" \
            -X POST -d "$data" "$url" | tr -d '\r' | grep -i '^location:' | awk '{print $2}'
    else
        curl -s -o /dev/null -D - -b "$jar" -c "$jar" "$url" | tr -d '\r' | grep -i '^location:' | awk '{print $2}'
    fi
}

# ── Helper: HTTP status code for a URL ─────────────────────────────
get_status() {
    local jar="$1" url="$2"
    curl -s -o /dev/null -w "%{http_code}" -b "$jar" "$url"
}

# ── Helper: extract CSRF token from login page ─────────────────────
get_csrf() {
    local jar="$1"
    curl -s -b "$jar" -c "$jar" "$BASE/login" | grep -oE 'name="csrf_test_name"[^>]*value="[^"]+"' | head -1 | sed 's/.*value="\([^"]*\)".*/\1/'
}

# ── Login helper ───────────────────────────────────────────────────
do_login() {
    local jar="$1" email="$2" password="$3"
    local csrf
    csrf=$(get_csrf "$jar")
    curl -s -o /dev/null -D - -b "$jar" -c "$jar" \
        -X POST -d "csrf_test_name=$csrf&email=$email&password=$password" \
        "$BASE/login" | tr -d '\r' | grep -i '^location:' | awk '{print $2}'
}

# =========================================================================
echo
echo -e "$INFO  TEST 1: anonymous can see /login (no auto-redirect)"
status=$(get_status "$COOKIE_DIR/anon.jar" "$BASE/login")
assert_eq "GET /login returns 200" "200" "$status"

# =========================================================================
echo
echo -e "$INFO  TEST 2: anonymous /admin/* and /owner/* → /login"
admin_status=$(get_status "$COOKIE_DIR/anon.jar" "$BASE/admin/dashboard")
owner_status=$(get_status "$COOKIE_DIR/anon.jar" "$BASE/owner/dashboard")
admin_redir=$(get_redirect "$COOKIE_DIR/anon.jar" "$BASE/admin/dashboard")
owner_redir=$(get_redirect "$COOKIE_DIR/anon.jar" "$BASE/owner/dashboard")
assert_eq "GET /admin/dashboard anonymously returns 302" "302" "$admin_status"
assert_eq "GET /owner/dashboard anonymously returns 302" "302" "$owner_status"
echo "    /admin/dashboard → $admin_redir"
echo "    /owner/dashboard → $owner_redir"

# =========================================================================
echo
echo -e "$INFO  TEST 3: login as admin → redirect /admin/dashboard"
JAR_ADMIN="$COOKIE_DIR/admin.jar"
redir=$(do_login "$JAR_ADMIN" "admin@swbeautysalon.local" "Password123%21")
echo "    POST /login → $redir"
case "$redir" in
    *admin/dashboard*) echo -e "  $PASS  redirect contains admin/dashboard" ;;
    *) echo -e "  $FAIL  expected admin/dashboard, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac

# =========================================================================
echo
echo -e "$INFO  TEST 4: admin → /owner/dashboard rejected (302 to /admin)"
status=$(get_status "$JAR_ADMIN" "$BASE/owner/dashboard")
redir=$(get_redirect "$JAR_ADMIN" "$BASE/owner/dashboard")
assert_eq "/owner/dashboard returns 302 for admin" "302" "$status"
case "$redir" in
    *admin/dashboard*) echo -e "  $PASS  admin bounced to /admin/dashboard" ;;
    *) echo -e "  $FAIL  expected admin/dashboard, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac

# =========================================================================
echo
echo -e "$INFO  TEST 5: admin dashboard has NO revenue/chart/top-services"
html=$(curl -s -b "$JAR_ADMIN" "$BASE/admin/dashboard")
for forbidden in "Pendapatan hari ini" "chart7days" "Layanan terpopuler"; do
    if echo "$html" | grep -q "$forbidden"; then
        echo -e "  $FAIL  admin dashboard leaks '$forbidden'"
        fail_count=$((fail_count + 1))
    else
        echo -e "  $PASS  admin dashboard hides '$forbidden'"
    fi
done

# =========================================================================
echo
echo -e "$INFO  TEST 6: logout (GET) clears session → /login form again"
redir=$(get_redirect "$JAR_ADMIN" "$BASE/logout")
echo "    GET /logout → $redir"
case "$redir" in
    *login*) echo -e "  $PASS  logout redirects to /login" ;;
    *) echo -e "  $FAIL  expected /login, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac
# After logout, /admin/dashboard should send us to /login again
admin_status=$(get_status "$JAR_ADMIN" "$BASE/admin/dashboard")
assert_eq "after logout, /admin/dashboard → 302" "302" "$admin_status"
redir=$(get_redirect "$JAR_ADMIN" "$BASE/admin/dashboard")
case "$redir" in
    *login*) echo -e "  $PASS  /admin/dashboard bounces to /login post-logout" ;;
    *) echo -e "  $FAIL  expected /login, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac
# And GET /login itself should return 200 (form), not auto-redirect somewhere
login_status=$(get_status "$JAR_ADMIN" "$BASE/login")
assert_eq "GET /login after logout returns 200" "200" "$login_status"

# =========================================================================
echo
echo -e "$INFO  TEST 7: re-login as pemilik on the same cookie jar → /owner/dashboard"
redir=$(do_login "$JAR_ADMIN" "owner@swbeautysalon.local" "Password123%21")
echo "    POST /login → $redir"
case "$redir" in
    *owner/dashboard*) echo -e "  $PASS  pemilik redirected to /owner/dashboard (NOT /admin)" ;;
    *) echo -e "  $FAIL  expected /owner/dashboard, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac
status=$(get_status "$JAR_ADMIN" "$BASE/owner/dashboard")
assert_eq "/owner/dashboard returns 200 for pemilik" "200" "$status"

# =========================================================================
echo
echo -e "$INFO  TEST 8: pemilik can ALSO access /admin/dashboard (read-only)"
status=$(get_status "$JAR_ADMIN" "$BASE/admin/dashboard")
assert_eq "/admin/dashboard returns 200 for pemilik" "200" "$status"

# =========================================================================
echo
echo -e "$INFO  TEST 9: pemilik /owner/dashboard has revenue chart"
html=$(curl -s -b "$JAR_ADMIN" "$BASE/owner/dashboard")
for required in "Pendapatan hari ini" "chart7days" "Layanan terpopuler"; do
    if echo "$html" | grep -q "$required"; then
        echo -e "  $PASS  owner dashboard renders '$required'"
    else
        echo -e "  $FAIL  owner dashboard missing '$required'"
        fail_count=$((fail_count + 1))
    fi
done

# =========================================================================
echo
echo -e "$INFO  TEST 10: in-place account switch (admin → pemilik on same jar w/o logout)"
JAR_SWITCH="$COOKIE_DIR/switch.jar"
do_login "$JAR_SWITCH" "admin@swbeautysalon.local" "Password123%21" > /dev/null
role_after_admin=$(curl -s -b "$JAR_SWITCH" "$BASE/admin/dashboard" | grep -oE 'Dashboard · Admin|Dashboard · Pemilik' | head -1)
echo "    after admin login, dashboard reports: '$role_after_admin'"
# Now POST owner credentials WITHOUT logout
do_login "$JAR_SWITCH" "owner@swbeautysalon.local" "Password123%21" > /dev/null
role_after_owner=$(curl -s -b "$JAR_SWITCH" "$BASE/owner/dashboard" | grep -oE 'Dashboard · Pemilik' | head -1)
echo "    after re-login as pemilik, dashboard reports: '$role_after_owner'"
case "$role_after_owner" in
    "Dashboard · Pemilik") echo -e "  $PASS  session switched to pemilik cleanly" ;;
    *) echo -e "  $FAIL  session did not switch — still seeing admin?"; fail_count=$((fail_count + 1)) ;;
esac

# =========================================================================
echo
echo -e "$INFO  TEST 11: pelanggan filter — anonymous /pelanggan/dashboard"
status=$(get_status "$COOKIE_DIR/anon2.jar" "$BASE/pelanggan/dashboard")
redir=$(get_redirect "$COOKIE_DIR/anon2.jar" "$BASE/pelanggan/dashboard")
assert_eq "/pelanggan/dashboard anon → 302" "302" "$status"
case "$redir" in
    *login*) echo -e "  $PASS  anonymous bounced to /login" ;;
    *) echo -e "  $FAIL  expected /login, got: $redir"; fail_count=$((fail_count + 1)) ;;
esac

# =========================================================================
echo
echo "──────────────────────────────────────────"
if [ "$fail_count" -eq 0 ]; then
    echo -e "  $PASS  All tests passed."
    exit 0
else
    echo -e "  $FAIL  $fail_count assertion(s) failed."
    exit 1
fi
