<?php
session_start();

// Connect to the ProjectHub database.
$conn = new mysqli("localhost", "root", "", "projecthub_db");
if ($conn->connect_error) {
    die("Database connection failed. Please check your MySQL settings.");
}
$conn->set_charset("utf8mb4");

$page = $_GET["page"] ?? (isset($_SESSION["user_id"]) ? "dashboard" : "login");
$error = "";
$message = "";

// Handle login and registration.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["auth_action"])) {
    $action = $_POST["auth_action"];
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($action === "register") {
        $name = trim($_POST["name"] ?? "");

        if ($name === "" || $email === "" || $password === "") {
            $error = "Please fill in all required fields.";
            $page = "register";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
            $page = "register";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = "Use at least 8 characters, including uppercase, lowercase and a number.";
            $page = "register";
        } else {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $error = "An account with this email already exists.";
                $page = "register";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hash);
                if ($stmt->execute()) {
                    $_SESSION["user_id"] = $stmt->insert_id;
                    $_SESSION["user_name"] = $name;
                    header("Location: main.php?page=dashboard");
                    exit;
                }
                $error = "Could not create your account. Please try again.";
                $page = "register";
                $stmt->close();
            }
        }
    } elseif ($action === "login") {
        if ($email === "" || $password === "") {
            $error = "Please enter your email and password.";
            $page = "login";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
            $page = "login";
        } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase and a number.";
            $page = "login";
        } else {
            $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            header("Location: main.php?page=dashboard");
            exit;
            } else {
                $error = "Incorrect email or password.";
                $page = "login";
            }
        }
    }
}

// Log out.
if ($page === "logout") {
    session_unset();
    session_destroy();
    header("Location: main.php?page=login");
    exit;
}

// Handle project and team member changes.
if (isset($_SESSION["user_id"]) && $_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = (int)$_SESSION["user_id"];

    if (isset($_POST["project_action"])) {
        $action = $_POST["project_action"];

        if ($action === "save") {
            $projectId = (int)($_POST["project_id"] ?? 0);
            $projectName = trim($_POST["project_name"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $priority = $_POST["priority"] ?? "Medium";
            $dueDate = $_POST["due_date"] ?? "";
            $status = $_POST["status"] ?? "Pending";

            if ($projectName === "" || $description === "" || $dueDate === "") {
                $error = "Please fill in every project field.";
            } elseif (!in_array($priority, ["Low", "Medium", "High"], true)) {
                $error = "Please choose a valid priority.";
            } elseif (!in_array($status, ["Pending", "In Progress", "Completed"], true)) {
                $error = "Please choose a valid status.";
            } elseif (!preg_match('/^\\d{2}-\\d{2}-\\d{4}$/', $dueDate)) {
                $error = "Enter the due date as DD-MM-YYYY.";
            } else {
                $dateParts = explode("-", $dueDate);
                if (!checkdate((int)$dateParts[1], (int)$dateParts[0], (int)$dateParts[2])) {
                    $error = "Enter a valid calendar date.";
                } else {
                    $dueDate = $dateParts[2] . "-" . $dateParts[1] . "-" . $dateParts[0];
                    if ($dueDate < date("Y-m-d")) {
                        $error = "Due date cannot be in the past.";
                    }
                }
            }
            if (!$error) {

                if ($projectId > 0) {
                    $stmt = $conn->prepare("UPDATE projects SET project_name=?, description=?, priority=?, due_date=?, status=? WHERE id=? AND user_id=?");
                    $stmt->bind_param("sssssii", $projectName, $description, $priority, $dueDate, $status, $projectId, $userId);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Project updated successfully.";
                } else {
                    $stmt = $conn->prepare("INSERT INTO projects (user_id, project_name, description, priority, due_date, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssss", $userId, $projectName, $description, $priority, $dueDate, $status);
                    $stmt->execute();
                    $stmt->close();
                    $message = "Project added successfully.";
                }
            }
        } elseif ($action === "delete") {
            $projectId = (int)($_POST["project_id"] ?? 0);
            $stmt = $conn->prepare("DELETE FROM projects WHERE id=? AND user_id=?");
            $stmt->bind_param("ii", $projectId, $userId);
            $stmt->execute();
            $stmt->close();
            $message = "Project deleted.";
        }
        $page = "projects";
    }

    if (isset($_POST["member_action"])) {
        $action = $_POST["member_action"];
        $projectId = (int)($_POST["project_id"] ?? 0);

        // Make sure the selected project belongs to this account.
        $check = $conn->prepare("SELECT id FROM projects WHERE id=? AND user_id=?");
        $check->bind_param("ii", $projectId, $userId);
        $check->execute();
        $projectExists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$projectExists) {
            $error = "Please select one of your projects first.";
        } elseif ($action === "add") {
            $memberName = trim($_POST["member_name"] ?? "");
            $memberEmail = trim($_POST["member_email"] ?? "");
            if ($memberName === "" || $memberEmail === "") {
                $error = "Please fill in every team member field.";
            } elseif (!filter_var($memberEmail, FILTER_VALIDATE_EMAIL)) {
                $error = "Please enter a valid team member email address.";
            } else {
                $stmt = $conn->prepare("INSERT INTO team_members (project_id, name, email) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $projectId, $memberName, $memberEmail);
                $stmt->execute();
                $stmt->close();
                $message = "Team member added.";
            }
        } elseif ($action === "delete") {
            $memberId = (int)($_POST["member_id"] ?? 0);
            $stmt = $conn->prepare("DELETE team_members FROM team_members JOIN projects ON team_members.project_id=projects.id WHERE team_members.id=? AND projects.user_id=?");
            $stmt->bind_param("ii", $memberId, $userId);
            $stmt->execute();
            $stmt->close();
            $message = "Team member removed.";
        }
        $page = "team";
    }
}

if (!isset($_SESSION["user_id"]) && !in_array($page, ["login", "register"], true)) {
    header("Location: main.php?page=login");
    exit;
}

$editingProject = null;
if (isset($_SESSION["user_id"]) && isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $editId, $_SESSION["user_id"]);
    $stmt->execute();
    $editingProject = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $page = "projects";
}

$projects = [];
$ownedProjects = [];
$members = [];
$counts = ["total" => 0, "pending" => 0, "progress" => 0, "completed" => 0];

if (isset($_SESSION["user_id"])) {
    $userId = (int)$_SESSION["user_id"];

    // Get projects created by this user.
    $stmt = $conn->prepare("SELECT * FROM projects WHERE user_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $ownedProjects[] = $row;
    $stmt->close();

    // Also show projects where the user's registered email is assigned as a team member.
    $stmt = $conn->prepare("SELECT DISTINCT p.*, (p.user_id = ?) AS is_owner
        FROM projects p
        JOIN users u ON u.id = ?
        LEFT JOIN team_members tm ON tm.project_id = p.id
        WHERE p.user_id = u.id OR LOWER(TRIM(tm.email)) = LOWER(TRIM(u.email))
        ORDER BY p.created_at DESC");
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
        $counts["total"]++;
        if ($row["status"] === "Pending") $counts["pending"]++;
        if ($row["status"] === "In Progress") $counts["progress"]++;
        if ($row["status"] === "Completed") $counts["completed"]++;
    }
    $stmt->close();

    // Team Directory shows only members added to projects owned by the logged-in user.
    // Do not show members from projects created by other users, even if the current user is assigned there.
    $stmt = $conn->prepare("SELECT tm.name AS name, tm.email AS email,
                p.project_name AS project_name, creator.name AS created_by
        FROM team_members tm
        JOIN projects p ON p.id = tm.project_id
        JOIN users creator ON creator.id = p.user_id
        WHERE p.user_id = ?
        ORDER BY p.project_name, tm.name");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $members[] = $row;
    $stmt->close();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProjectHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if ($page === "login" || $page === "register"): ?>
    <main class="auth-page">
        <section class="auth-layout">
            <div class="auth-editorial-layout">
                <section class="auth-editorial-copy">
                    <a class="auth-brand" href="main.php?page=login">ProjectHub</a>
                    <div class="auth-editorial-heading">
                        <p class="eyebrow">YOUR WORKSPACE, REIMAGINED</p>
                        <h1>Bring your ideas to life.</h1>
                        <p class="auth-subtitle">A shared space to organise projects, manage tasks, and collaborate with your team.</p>
                    </div>
                    <div class="homepage-illustration">
                        <img src="images/teamwork.jpg" alt="Team collaborating on project tasks">
                    </div>
                </section>

                <section class="auth-editorial-forms">
            <div class="auth-forms">
                <section class="auth-panel">
                    <h2>Login</h2>
                    <p class="panel-subtitle">Welcome back. Please enter your details.</p>
                    <?php if ($error && $page === "login"): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form novalidate method="post" action="main.php?page=login">
                        <input type="hidden" name="auth_action" value="login">
                        <label class="form-label" for="login-email">Email</label>
                        <input class="form-control" id="login-email" type="email" name="email" value="<?= (($_POST["auth_action"] ?? "") === "login") ? e($_POST["email"] ?? "") : "" ?>" autocomplete="username" required>
                        <label class="form-label mt-3" for="login-password">Password</label>
                        <input class="form-control" id="login-password" type="password" name="password" required>
                        <button class="btn btn-main w-100 mt-4" type="submit">Login</button>
                    </form>
                </section>

                <section class="auth-panel">
                    <h2>Create an account</h2>
                    <p class="panel-subtitle">New here? Create your account to get started.</p>
                    <?php if ($error && $page === "register"): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                    <form novalidate method="post" action="main.php?page=register">
                        <input type="hidden" name="auth_action" value="register">
                        <label class="form-label" for="register-name">Name</label>
                        <input class="form-control" id="register-name" type="text" name="name" value="<?= (($_POST["auth_action"] ?? "") === "register") ? e($_POST["name"] ?? "") : "" ?>" autocomplete="name" required>
                        <label class="form-label mt-3" for="register-email">Email</label>
                        <input class="form-control" id="register-email" type="email" name="email" value="<?= (($_POST["auth_action"] ?? "") === "register") ? e($_POST["email"] ?? "") : "" ?>" autocomplete="email" required>
                        <label class="form-label mt-3" for="register-password">Password</label>
                        <input class="form-control" id="register-password" type="password" name="password" minlength="8" required>
                        <button class="btn btn-main w-100 mt-4" type="submit">Create Account</button>
                    </form>
                </section>
            </div>
                </section>
            </div>
        </section>
    </main>
<?php else: ?>
    <aside class="sidebar">
        <a class="brand" href="main.php?page=dashboard"><span>ProjectHub</span></a>
        <p class="sidebar-note">A little more organised.</p>
        <nav>
            <a class="<?= $page === "dashboard" ? "active" : "" ?>" href="main.php?page=dashboard">Dashboard</a>
            <a class="<?= $page === "projects" ? "active" : "" ?>" href="main.php?page=projects">Projects</a>
            <a class="<?= $page === "team" ? "active" : "" ?>" href="main.php?page=team">Team Members</a>
            <a class="<?= $page === "directory" ? "active" : "" ?>" href="main.php?page=directory">Team Directory</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-chip"><span class="avatar"><?= e(strtoupper(substr($_SESSION["user_name"], 0, 1))) ?></span><span><?= e($_SESSION["user_name"]) ?></span></div>
            <a href="main.php?page=logout">Log out</a>
        </div>
    </aside>

    <main class="main-content">
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <?php if ($page === "dashboard"): ?>
            <div class="page-heading">
                <div><p class="eyebrow">YOUR OVERVIEW</p><h1>Hello, <?= e($_SESSION["user_name"]) ?>.</h1></div>
                <a class="btn btn-main" href="main.php?page=projects">+ Add project</a>
            </div>
            <div class="row g-3 summary-row">
                <div class="col-6 col-xl-3"><div class="stat-card"><span>Total projects</span><strong><?= $counts["total"] ?></strong></div></div>
                <div class="col-6 col-xl-3"><div class="stat-card"><span>Pending</span><strong><?= $counts["pending"] ?></strong></div></div>
                <div class="col-6 col-xl-3"><div class="stat-card"><span>In progress</span><strong><?= $counts["progress"] ?></strong></div></div>
                <div class="col-6 col-xl-3"><div class="stat-card"><span>Completed</span><strong><?= $counts["completed"] ?></strong></div></div>
            </div>
            <section class="content-card">
                <div class="section-heading"><div><p class="eyebrow">WORKSPACE</p><h2>Recent projects</h2></div><a href="main.php?page=projects">View all →</a></div>
                <?php if (!$projects): ?>
                    <div class="empty-state"><h3>Your workspace starts here</h3><p>Add a project and keep its team, priority, due date and status together.</p><a class="btn btn-main" href="main.php?page=projects">Create a project</a></div>
                <?php else: ?>
                    <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                        <article class="project-card">
                            <div class="project-card-top"><span class="priority priority-<?= strtolower(e($project["priority"])) ?>"><?= e($project["priority"]) ?> priority</span><span class="status"><?= e($project["status"]) ?></span></div>
                            <h3><?= e($project["project_name"]) ?></h3>
                            <p><?= e($project["description"] ?: "No description added yet.") ?></p>
                            <div class="project-meta"><span>Due: <?= e($project["due_date"] ?: "Not set") ?></span></div>
                            <a class="text-link" href="main.php?page=projects&edit=<?= (int)$project["id"] ?>">Edit project →</a>
                        </article>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($page === "projects"): ?>
            <div class="page-heading">
                <div><p class="eyebrow">YOUR WORKSPACE</p><h1>Projects</h1><p class="muted">Manage project details, priority, due dates and status.</p></div>
                <a class="btn btn-light-outline" href="main.php?page=projects">+ New project</a>
            </div>
            <div class="projects-layout single-page-layout">
                <section class="content-card project-form-card">
                    <h2><?= $editingProject ? "Edit project" : "Add a project" ?></h2>
                    <form novalidate method="post" action="main.php?page=projects">
                        <input type="hidden" name="project_action" value="save">
                        <input type="hidden" name="project_id" value="<?= (int)($editingProject["id"] ?? 0) ?>">
                        <label class="form-label" for="project-name">Project name</label>
                        <input class="form-control" id="project-name" name="project_name" value="<?= e($_POST["project_name"] ?? ($editingProject["project_name"] ?? "")) ?>" required>
                        <label class="form-label mt-3" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?= e($_POST["description"] ?? ($editingProject["description"] ?? "")) ?></textarea>
                        <div class="row g-3 mt-1">
                            <div class="col-sm-6"><label class="form-label" for="priority">Priority</label><select class="form-select" id="priority" name="priority"><?php foreach (["Low", "Medium", "High"] as $option): ?><option <?= (($_POST["priority"] ?? ($editingProject["priority"] ?? "Medium")) === $option) ? "selected" : "" ?>><?= $option ?></option><?php endforeach; ?></select></div>
                            <div class="col-sm-6"><label class="form-label" for="due-date">Due date</label><input class="form-control" id="due-date" type="text" name="due_date" value="<?= e($_POST["due_date"] ?? (!empty($editingProject["due_date"]) ? date("d-m-Y", strtotime($editingProject["due_date"])) : "")) ?>" placeholder="DD-MM-YYYY" inputmode="numeric" maxlength="10" pattern="[0-9]{2}-[0-9]{2}-[0-9]{4}" title="Enter date as DD-MM-YYYY" title="Enter the date as YYYY-MM-DD" required></div>
                        </div>
                        <label class="form-label mt-3" for="status">Status</label>
                        <select class="form-select" id="status" name="status"><?php foreach (["Pending", "In Progress", "Completed"] as $option): ?><option <?= (($_POST["status"] ?? ($editingProject["status"] ?? "Pending")) === $option) ? "selected" : "" ?>><?= $option ?></option><?php endforeach; ?></select>
                        <button class="btn btn-main w-100 mt-4" type="submit"><?= $editingProject ? "Save changes" : "Add project" ?></button>
                        <?php if ($editingProject): ?><a class="cancel-link" href="main.php?page=projects">Cancel editing</a><?php endif; ?>
                    </form>
                </section>

                <section class="content-card project-list">
                    <div class="section-heading"><div><p class="eyebrow">PROJECT LIST</p><h2>Your projects</h2></div><span class="count-pill"><?= count($projects) ?> total</span></div>
                    <input class="form-control mb-3" type="search" data-table-search="#project-table" placeholder="Search your projects...">
                    <?php if (!$projects): ?>
                        <div class="empty-state small-empty"><h3>No projects yet</h3><p>Use the form to add your first project.</p></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table" id="project-table">
                                <thead><tr><th>Project</th><th>Priority</th><th>Due date</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td><strong><?= e($project["project_name"]) ?></strong><small><?= e($project["description"] ?: "No description") ?></small></td>
                                        <td><span class="priority priority-<?= strtolower(e($project["priority"])) ?>"><?= e($project["priority"]) ?></span></td>
                                        <td><?= !empty($project["due_date"]) ? e(date("d-m-Y", strtotime($project["due_date"]))) : "—" ?></td>
                                        <td><span class="status"><?= e($project["status"]) ?></span></td>
                                        <td class="actions"><?php if ((int)$project["user_id"] === (int)$_SESSION["user_id"]): ?><a href="main.php?page=projects&edit=<?= (int)$project["id"] ?>">Edit</a><form novalidate method="post" action="main.php?page=projects" class="inline-form"><input type="hidden" name="project_action" value="delete"><input type="hidden" name="project_id" value="<?= (int)$project["id"] ?>"><button type="submit" class="delete-button">Delete</button></form><?php else: ?><span class="muted">Assigned to you</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        <?php elseif ($page === "directory"): ?>
            <div class="page-heading">
                <div>
                    <p class="eyebrow">YOUR WORKSPACE</p>
                    <h1>Team Directory</h1>
                    <p class="muted">View team members and the projects they belong to.</p>
                </div>
                <a class="btn btn-main" href="main.php?page=team">+ Add team member</a>
            </div>

            <section class="content-card">
                <div class="section-heading">
                    <div><p class="eyebrow">ALL MEMBERS</p><h2>Team directory</h2></div>
                    
                </div>
                <input class="form-control mb-3" type="search" data-table-search="#directory-table" placeholder="Search by name, email or project...">
                <?php if (!$members): ?>
                    <div class="empty-state">
                        <h3>No team members yet</h3>
                        <p>Add team members to your projects and they will appear here.</p>
                        <a class="btn btn-main" href="main.php?page=team">Add a team member</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="directory-table">
                            <thead><tr><th>Name</th><th>Email</th><th>Project</th><th>Created by</th></tr></thead>
                            <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><strong><?= e($member["name"]) ?></strong></td>
                                    <td><?= e($member["email"] ?: "—") ?></td>
                                    <td><?= e($member["project_name"]) ?></td>
                                    <td><?= e($member["created_by"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        <?php else: ?>
            <div class="page-heading">
                <div><p class="eyebrow">YOUR WORKSPACE</p><h1>Team Members</h1><p class="muted">Add or remove members assigned to your projects.</p></div>
            </div>

            <section class="content-card">
                <div class="section-heading"><div><p class="eyebrow">TEAM</p><h2>Add a team member</h2></div></div>
                <?php if (!$ownedProjects): ?>
                    <p class="muted">Create a project first to add team members.</p>
                    <a class="btn btn-main" href="main.php?page=projects">Go to Projects</a>
                <?php else: ?>
                    <form novalidate method="post" action="main.php?page=team" class="member-form">
                        <input type="hidden" name="member_action" value="add">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label" for="member-name">Name</label><input class="form-control" id="member-name" name="member_name" value="<?= e($_POST["member_name"] ?? "") ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="member-email">Email</label><input class="form-control" id="member-email" type="email" name="member_email" value="<?= e($_POST["member_email"] ?? "") ?>" required></div>
                            <div class="col-md-6"><label class="form-label" for="member-project">Project</label><select class="form-select" id="member-project" name="project_id" required><?php foreach ($ownedProjects as $project): ?><option value="<?= (int)$project["id"] ?>" <?= ((int)($_POST["project_id"] ?? 0) === (int)$project["id"]) ? "selected" : "" ?>><?= e($project["project_name"]) ?></option><?php endforeach; ?></select></div>
                        </div>
                        <button class="btn btn-main mt-3" type="submit">Add team member</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
<?php endif; ?>
<script src="script.js"></script>
</body>
</html>