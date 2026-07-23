<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Torq CRM — User Manual</title>
    <style>
        @page { margin: 125px 32px 42px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            color: #0f172a;
        }
        .page-header {
            position: fixed;
            top: -112px;
            left: 0;
            right: 0;
            height: 100px;
            border-bottom: 2px solid #1b3c6a;
            padding-bottom: 8px;
        }
        .page-footer {
            position: fixed;
            bottom: -28px;
            left: 0;
            right: 0;
            height: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { border: none; vertical-align: top; padding: 0; }
        .header-logo-cell { width: 150px; padding-right: 10px; vertical-align: middle; }
        .header-logo { max-height: 95px; max-width: 145px; }
        .header-info { text-align: center; vertical-align: middle; }
        .header-info h1 { margin: 0; font-size: 18px; color: #1b3c6a; letter-spacing: 0.5px; text-align: center; }
        .header-contact-cell { width: 170px; vertical-align: middle; text-align: right; font-size: 10px; color: #333; line-height: 1.55; white-space: nowrap; }
        .header-address { margin: 10px 0 0; font-size: 10px; color: #333; text-align: center; }

        h1.doc-title { font-size: 22px; margin: 0 0 6px; color: #1b3c6a; }
        h2 {
            font-size: 14px;
            margin: 20px 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #1b3c6a;
            color: #1b3c6a;
            page-break-after: avoid;
        }
        h3 {
            font-size: 12px;
            margin: 14px 0 6px;
            color: #1b3c6a;
            page-break-after: avoid;
        }
        p { margin: 0 0 8px; }
        ul, ol { margin: 0 0 10px; padding-left: 18px; }
        li { margin-bottom: 3px; }
        .cover {
            text-align: center;
            padding: 48px 20px 28px;
            page-break-after: always;
        }
        .cover .badge {
            display: inline-block;
            background: #1b3c6a;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 10px;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        .cover h1 { font-size: 26px; margin-bottom: 8px; color: #1b3c6a; }
        .cover .subtitle { color: #64748b; font-size: 12px; margin-bottom: 28px; }
        .cover .meta { color: #94a3b8; font-size: 10px; margin-top: 36px; }
        .toc li { margin-bottom: 5px; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 14px;
            font-size: 10px;
        }
        table.data th, table.data td {
            border: 1px solid #d0d5dd;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        table.data th { background: #f3f5f8; color: #334155; }
        .note {
            background: #eff6ff;
            border-left: 3px solid #1b3c6a;
            padding: 8px 10px;
            margin: 10px 0;
        }
        .warn {
            background: #fff7ed;
            border-left: 3px solid #f59e0b;
            padding: 8px 10px;
            margin: 10px 0;
        }
        .step {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin: 8px 0;
        }
        .kbd {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 9px;
        }
        .page-break { page-break-before: always; }
        .footer-note {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page-header">
        @include('quotations.partials.document-header', ['company' => $company])
    </div>

    <div class="page-footer">
        <span class="page-footer-line"></span>
    </div>

    <div class="cover">
        <div class="badge">USER MANUAL</div>
        <h1>Torq CRM</h1>
        <p class="subtitle">Lead Management &amp; Sales Console<br>How to use the application day to day</p>
        <p>Version 1.0 &nbsp;|&nbsp; {{ now()->format('d M Y') }}</p>
        <p class="meta">For Sales, Marketing, Managers &amp; Administrators</p>
    </div>

    <h2>Table of Contents</h2>
    <ol class="toc">
        <li>Getting Started — Login</li>
        <li>Roles &amp; Access</li>
        <li>Dashboard (Lead Console)</li>
        <li>Leads — My Leads &amp; All Leads</li>
        <li>Lead Detail — Activity &amp; Actions</li>
        <li>Followups — My &amp; All</li>
        <li>Customers, Companies &amp; Products</li>
        <li>Quotations &amp; Quotation Terms</li>
        <li>Tasks</li>
        <li>Administration</li>
        <li>IndiaMART Sync</li>
        <li>Quick Tips &amp; Troubleshooting</li>
    </ol>

    <div class="page-break"></div>
    <h2>1. Getting Started — Login</h2>
    <p>Open the Torq CRM URL provided by your company (example: your server <span class="kbd">/login</span> page).</p>
    <div class="step">
        <strong>Steps</strong>
        <ol>
            <li>Enter <strong>Email</strong>, <strong>Username</strong>, or <strong>Mobile</strong>.</li>
            <li>Enter your <strong>Password</strong>.</li>
            <li>Optional: tick <strong>Remember Me</strong>.</li>
            <li>Click <strong>Sign In</strong>.</li>
        </ol>
    </div>
    <p>After login you land on the <strong>Dashboard</strong> (if you have dashboard permission).</p>
    <p>Use the top-right user menu for <strong>Profile</strong>, <strong>Dashboard</strong>, or <strong>Sign Out</strong>.</p>
    <p>Forgot password? Use <strong>Forgot Password</strong> on the login screen and follow the email reset link.</p>

    <h2>2. Roles &amp; Access</h2>
    <p>What you see depends on your role. Menus appear only if you have permission.</p>
    <table class="data">
        <thead>
            <tr>
                <th>Role</th>
                <th>Typical access</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Super Admin</strong></td>
                <td>Full access — Operations + Administration (users, roles, permissions, company settings).</td>
            </tr>
            <tr>
                <td><strong>Admin</strong></td>
                <td>Almost full access. Same as Super Admin except cannot delete roles.</td>
            </tr>
            <tr>
                <td><strong>Manager</strong></td>
                <td>Leads, Followups, Customers, Companies, Products, Quotations, Tasks. Can see <strong>All</strong> records. No Administration menu.</td>
            </tr>
            <tr>
                <td><strong>Marketing</strong></td>
                <td>Leads, Followups, Customers, Quotations, Tasks (as permitted). Focused on <strong>My</strong> assigned work. No Administration menu.</td>
            </tr>
        </tbody>
    </table>
    <div class="note">
        <strong>My vs All:</strong> <em>My Leads / My Followups</em> show only records assigned to you.
        <em>All Leads / All Followups</em> show the wider team list (as allowed by your role).
    </div>

    <h2>3. Dashboard (Lead Console)</h2>
    <p>Path: Sidebar → <strong>Dashboard</strong></p>
    <p>Use the dashboard for a quick health check of today’s pipeline.</p>
    <h3>Stat cards</h3>
    <table class="data">
        <thead>
            <tr><th>Card</th><th>Meaning</th></tr>
        </thead>
        <tbody>
            <tr><td>Today's Lead</td><td>Leads created today.</td></tr>
            <tr><td>Open Lead</td><td>Active pipeline (not Won / Lost / Junk / Duplicate).</td></tr>
            <tr><td>Contacted Lead</td><td>Leads currently in Contacted status.</td></tr>
            <tr><td>Today's Rejected Lead</td><td>Leads marked Lost today.</td></tr>
            <tr><td>Today's Lead Followup</td><td>Open leads with follow-up due today.</td></tr>
            <tr><td>Overdue Lead Followup</td><td>Open leads whose follow-up date is past.</td></tr>
            <tr><td>Unassigned Lead</td><td>Open leads with no assignee.</td></tr>
        </tbody>
    </table>
    <p>Below the cards you will see revenue / conversion metrics and charts (sources, monthly trend, operational health).</p>
    <div class="warn">
        Dashboard <strong>Sync Leads</strong> refreshes console stats. To pull IndiaMART enquiries, use <strong>Sync</strong> on the Leads page.
    </div>

    <div class="page-break"></div>
    <h2>4. Leads — My Leads &amp; All Leads</h2>
    <p>Path: Sidebar → <strong>Leads</strong> → <strong>My Leads</strong> or <strong>All Leads</strong></p>
    <ul>
        <li><strong>My Leads</strong> — only leads assigned to you.</li>
        <li><strong>All Leads</strong> — all leads you are allowed to see.</li>
    </ul>
    <h3>List features</h3>
    <ul>
        <li>Search by lead number, customer, company, mobile, email.</li>
        <li>Filter by date range, status, source.</li>
        <li>Sort columns; paginate results.</li>
        <li>Click the eye icon to open lead detail.</li>
    </ul>
    <h3>Create a lead</h3>
    <div class="step">
        <ol>
            <li>Click <strong>Create Lead</strong>.</li>
            <li>Fill Customer Name (required), company, mobile, email, source, assignee, requirement.</li>
            <li>Save. A lead number is generated automatically (example: LD-000123).</li>
        </ol>
    </div>
    <h3>Lead statuses</h3>
    <p>New → Assigned → Contacted → Interested → Follow Up → Quotation Sent → Negotiation → Won / Lost / Junk / Duplicate</p>

    <h2>5. Lead Detail — Activity &amp; Actions</h2>
    <p>Path: open any lead (example URL <span class="kbd">/leads/700</span>)</p>
    <p>Top summary shows Status, Priority, Assigned, Mobile, Source, and Requirement.</p>
    <h3>Activity Timeline</h3>
    <p>Column view of history:</p>
    <ul>
        <li>Date &amp; Time</li>
        <li>Activity (Call, Meeting, Email, WhatsApp, SMS, Status Changed, Assigned, etc.)</li>
        <li>Description / notes</li>
        <li>Action By</li>
    </ul>
    <p>Newest activity appears first.</p>
    <h3>Record Lead Action</h3>
    <p>At the bottom of the page, use <strong>Record Lead Action</strong> to log work:</p>
    <table class="data">
        <thead>
            <tr><th>Field</th><th>How to use</th></tr>
        </thead>
        <tbody>
            <tr><td>Action Type</td><td>Call, Meeting, Email, WhatsApp, or SMS.</td></tr>
            <tr><td>Lead Status</td><td>Update pipeline stage.</td></tr>
            <tr>
                <td>Assign To</td>
                <td><strong>Admin:</strong> choose user from dropdown.<br><strong>Other users:</strong> shows assigned name. If unassigned, first submit assigns the lead to you.</td>
            </tr>
            <tr><td>Next Follow-up</td><td>Optional date/time for the next touch.</td></tr>
            <tr><td>Action Notes</td><td>Required — write what was discussed and next steps.</td></tr>
        </tbody>
    </table>
    <div class="step">
        Click <strong>Save Activity</strong>. The system saves the note, updates status/follow-up, creates timeline entry, and refreshes the page.
    </div>

    <h2>6. Followups — My &amp; All</h2>
    <p>Path: Sidebar → <strong>Followups</strong> → <strong>My Followups</strong> or <strong>All Followups</strong></p>
    <ul>
        <li>Shows leads that have a <strong>Next Followup Date</strong>.</li>
        <li>Sorted by next follow-up date (latest first by default).</li>
        <li>Filter by follow-up date range, status, source, and search.</li>
        <li>Open the lead to record the next activity.</li>
    </ul>
    <div class="note">
        Use this menu every morning to clear overdue and today’s follow-ups.
    </div>

    <div class="page-break"></div>
    <h2>7. Customers, Companies &amp; Products</h2>
    <h3>Customers</h3>
    <p>Sidebar → <strong>Customers</strong>. Search, filter Active/Inactive, add/edit customer details (name, mobile, email, company, status).</p>
    <h3>Companies</h3>
    <p>Sidebar → <strong>Companies</strong>. Maintain company name, phone, email, GST, city, state, active flag.</p>
    <h3>Products</h3>
    <p>Sidebar → <strong>Products</strong>. Maintain catalogue: name, SKU, category, unit, price, tax %, HSN, and related fields used in quotations.</p>

    <h2>8. Quotations &amp; Quotation Terms</h2>
    <h3>Quotations</h3>
    <ul>
        <li>Create and manage quotes linked to customers/leads.</li>
        <li>Statuses commonly include Draft, Sent, Accepted, Rejected, Expired.</li>
        <li>From quotation detail you can open or download <strong>PDF</strong>.</li>
    </ul>
    <h3>Quotation Terms</h3>
    <p>Maintain reusable Terms &amp; Conditions templates for quotations (default/active templates).</p>

    <h2>9. Tasks</h2>
    <p>Path: Sidebar → <strong>Tasks</strong> (also available from header shortcut).</p>
    <ul>
        <li>Create tasks with title, description, assignee, customer, priority, due date.</li>
        <li>Update status (e.g. Pending → Completed).</li>
        <li>Filter/search your task list.</li>
    </ul>

    <h2>10. Administration</h2>
    <p>Visible only to <strong>Super Admin</strong> and <strong>Admin</strong>.</p>
    <table class="data">
        <thead>
            <tr><th>Menu</th><th>Purpose</th></tr>
        </thead>
        <tbody>
            <tr><td>Users</td><td>Create/edit users, assign roles, activate/deactivate accounts.</td></tr>
            <tr><td>Roles</td><td>Define roles and attach permissions.</td></tr>
            <tr><td>Permissions</td><td>Browse permission groups; sync registry when needed.</td></tr>
            <tr><td>Company Profile</td><td>Company name, logo, address, quotation defaults/signatory.</td></tr>
            <tr><td>Settings</td><td>Personal profile and password (under user menu / Settings).</td></tr>
        </tbody>
    </table>

    <h2>11. IndiaMART Sync</h2>
    <p>Path: <strong>Leads</strong> page → toolbar <strong>Sync</strong></p>
    <div class="step">
        <ol>
            <li>Open My Leads or All Leads.</li>
            <li>Click <strong>Sync</strong>.</li>
            <li>Wait for the success message (inserted vs skipped/already existing).</li>
            <li>Refresh the list if needed.</li>
        </ol>
    </div>
    <p>New IndiaMART enquiries become CRM leads and can be assigned and worked through the lead action form.</p>

    <h2>12. Quick Tips &amp; Troubleshooting</h2>
    <table class="data">
        <thead>
            <tr><th>Situation</th><th>What to do</th></tr>
        </thead>
        <tbody>
            <tr><td>I cannot see a menu</td><td>Ask Admin to grant the related permission / role.</td></tr>
            <tr><td>I cannot edit a lead</td><td>Usually you can edit only leads assigned to you (unless Manager/Admin).</td></tr>
            <tr><td>Unassigned lead</td><td>Open lead → Save Activity. Non-admin users get auto-assigned on first action.</td></tr>
            <tr><td>Followup missing in list</td><td>Lead must have a Next Followup Date set from lead action/edit.</td></tr>
            <tr><td>Sync did nothing</td><td>Check IndiaMART source connectivity with Admin; review success toast for skipped count.</td></tr>
            <tr><td>Password forgotten</td><td>Use Forgot Password on login, or ask Admin to reset.</td></tr>
        </tbody>
    </table>

    <div class="note">
        <strong>Daily recommended flow:</strong>
        Dashboard → My Followups (overdue + today) → Open lead → Record Action → Update next follow-up → Check My Leads for new/unworked items.
    </div>

    <div class="footer-note">
        Torq CRM User Manual v1.0 &nbsp;•&nbsp; Generated {{ now()->format('d M Y, h:i A') }} &nbsp;•&nbsp; For internal training and day-to-day reference
    </div>
</body>
</html>
