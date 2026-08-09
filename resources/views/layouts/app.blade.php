<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ReLink: A Web-Based System for Lost Item Reporting and Recovery') }}</title>
    <style>
        :root {
            --ink: #15231f;
            --muted: #5f6d68;
            --line: #d8e1dc;
            --paper: #ffffff;
            --wash: #f4f7f2;
            --green: #0d5f43;
            --green-soft: #e3f4ec;
            --blue: #2563a8;
            --blue-soft: #e7f0fb;
            --amber: #a46108;
            --amber-soft: #fff4d7;
            --red: #b4232e;
            --red-soft: #fde8e8;
            --violet: #6650a4;
            --violet-soft: #eeeafd;
            --shadow: 0 16px 35px rgba(21, 35, 31, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--wash);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(244, 247, 242, 0.94);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            max-width: 1180px;
            margin: 0 auto;
            padding: 14px 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 800;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 30px 0 56px;
        }

        .page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 20px;
        }

        .eyebrow {
            margin: 0 0 6px;
            color: var(--green);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 8px;
            font-size: 2rem;
            line-height: 1.15;
        }

        h2 {
            margin-bottom: 14px;
            font-size: 1.2rem;
        }

        h3 {
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .muted {
            color: var(--muted);
        }

        .panel,
        .report-card,
        .stat-card,
        .auth-card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .panel,
        .auth-card {
            padding: 22px;
        }

        .button,
        .ghost-button,
        .danger-button,
        .nav-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 14px;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
        }

        .button {
            color: white;
            background: var(--green);
        }

        .ghost-button,
        .nav-button {
            color: var(--ink);
            background: white;
            border-color: var(--line);
        }

        .danger-button {
            color: white;
            background: var(--red);
        }

        .nav-button {
            min-height: 36px;
            padding: 0 12px;
        }

        .inline-form {
            display: inline;
        }

        .notice,
        .error-box {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid;
        }

        .notice {
            color: #124b35;
            background: var(--green-soft);
            border-color: #b4dec9;
        }

        .error-box {
            color: var(--red);
            background: var(--red-soft);
            border-color: #f3b6bb;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .live-stack {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 40;
            display: grid;
            width: min(380px, calc(100% - 32px));
            gap: 10px;
        }

        .live-notification {
            display: grid;
            gap: 8px;
            padding: 14px;
            background: white;
            border: 1px solid var(--line);
            border-left: 5px solid var(--green);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .live-notification strong,
        .live-notification p {
            margin: 0;
        }

        .live-notification a {
            color: var(--green);
            font-weight: 800;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat-card {
            padding: 16px;
        }

        .stat-card span {
            display: block;
            color: var(--muted);
            font-size: 0.86rem;
            font-weight: 700;
        }

        .stat-card strong {
            display: block;
            margin-top: 6px;
            font-size: 1.7rem;
            line-height: 1;
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 150px 180px auto;
            gap: 10px;
            margin-bottom: 18px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field label,
        .radio-label {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .input,
        .textarea,
        .select {
            width: 100%;
            min-height: 42px;
            padding: 9px 11px;
            color: var(--ink);
            background: white;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .textarea {
            min-height: 118px;
            resize: vertical;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .span-2 {
            grid-column: span 2;
        }

        .segmented {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .segment input {
            position: absolute;
            opacity: 0;
        }

        .segment span {
            display: block;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: white;
            font-weight: 800;
            text-align: center;
            cursor: pointer;
        }

        .segment input:checked + span {
            color: white;
            background: var(--green);
            border-color: var(--green);
        }

        .role-card {
            text-align: left;
        }

        .role-card strong,
        .role-card small {
            display: block;
        }

        .role-card small {
            margin-top: 4px;
            color: var(--muted);
            font-weight: 600;
        }

        .segment input:checked + .role-card small {
            color: rgba(255, 255, 255, 0.86);
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .report-card {
            overflow: hidden;
        }

        .report-photo,
        .photo-placeholder {
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            background: linear-gradient(135deg, var(--green-soft), var(--blue-soft));
        }

        .photo-placeholder {
            display: grid;
            place-items: center;
            color: var(--green);
            font-size: 2rem;
            font-weight: 900;
        }

        .report-body {
            padding: 15px;
        }

        .report-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            padding: 0 9px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: capitalize;
        }

        .badge-lost,
        .badge-rejected,
        .badge-blocked {
            color: var(--red);
            background: var(--red-soft);
        }

        .badge-found,
        .badge-approved,
        .badge-claimed,
        .badge-closed,
        .badge-archived {
            color: var(--green);
            background: var(--green-soft);
        }

        .badge-pending {
            color: var(--amber);
            background: var(--amber-soft);
        }

        .badge-category {
            color: var(--blue);
            background: var(--blue-soft);
        }

        .badge-admin {
            color: var(--violet);
            background: var(--violet-soft);
        }

        .empty-state {
            padding: 28px;
            text-align: center;
        }

        .detail-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0;
        }

        .detail-list div {
            padding: 12px;
            background: #f8faf8;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .detail-list dt {
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 800;
        }

        .detail-list dd {
            margin: 4px 0 0;
            font-weight: 700;
        }

        .admin-list {
            display: grid;
            gap: 14px;
        }

        .admin-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 18px;
            padding: 18px;
        }

        .admin-actions {
            display: grid;
            align-content: start;
            gap: 8px;
        }

        .admin-actions form,
        .admin-actions button {
            width: 100%;
        }

        .status-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .pager {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }

        .pager span {
            color: var(--muted);
        }

        @media (max-width: 900px) {
            .page-head {
                align-items: stretch;
                flex-direction: column;
            }

            .stats-grid,
            .report-grid,
            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .admin-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .nav {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                justify-content: flex-start;
            }

            .stats-grid,
            .report-grid,
            .form-grid,
            .toolbar,
            .detail-list {
                grid-template-columns: 1fr;
            }

            .span-2 {
                grid-column: span 1;
            }

            .live-stack {
                right: 12px;
                bottom: 12px;
                width: calc(100% - 24px);
            }
        }

        /* Holographic command-center theme */
        :root {
            --ink: #eefafa;
            --muted: #a7bdc1;
            --line: rgba(117, 237, 235, 0.28);
            --paper: rgba(13, 31, 34, 0.72);
            --wash: #13282b;
            --green: #62f3ee;
            --green-soft: rgba(98, 243, 238, 0.13);
            --blue: #8fc8ff;
            --blue-soft: rgba(143, 200, 255, 0.14);
            --amber: #f7c55f;
            --amber-soft: rgba(247, 197, 95, 0.14);
            --red: #ff776f;
            --red-soft: rgba(255, 119, 111, 0.14);
            --violet: #d8a7ff;
            --violet-soft: rgba(216, 167, 255, 0.15);
            --shadow: 0 22px 48px rgba(0, 0, 0, 0.32), 0 0 30px rgba(67, 229, 224, 0.08);
        }

        html {
            min-height: 100%;
            background: #0e1f22;
        }

        body {
            position: relative;
            min-height: 100vh;
            color: var(--ink);
            background:
                linear-gradient(90deg, rgba(83, 229, 224, 0.14) 1px, transparent 1px) left 40px top 96px / 92px 92px repeat-y,
                linear-gradient(rgba(83, 229, 224, 0.12) 1px, transparent 1px) right 48px top 72px / 92px 92px repeat-y,
                linear-gradient(135deg, #1e4140 0%, #172f32 48%, #102125 100%);
            overflow-x: hidden;
        }

        body::before,
        body::after {
            position: fixed;
            pointer-events: none;
            content: "";
        }

        body::before {
            inset: 26px 34px;
            z-index: 0;
            border: 1px solid rgba(114, 238, 235, 0.55);
            border-radius: 18px;
            box-shadow:
                inset 0 0 0 1px rgba(189, 255, 253, 0.12),
                inset 0 0 38px rgba(79, 225, 221, 0.08),
                0 0 28px rgba(87, 232, 229, 0.32);
        }

        body::after {
            inset: 42px 48px;
            z-index: 0;
            opacity: 0.42;
            background:
                linear-gradient(90deg, transparent 0 18px, rgba(109, 236, 232, 0.42) 18px 20px, transparent 20px 74px) left top / 240px 120px no-repeat,
                linear-gradient(rgba(109, 236, 232, 0.36) 0 2px, transparent 2px 48px) left top / 180px 96px no-repeat,
                linear-gradient(270deg, transparent 0 18px, rgba(109, 236, 232, 0.42) 18px 20px, transparent 20px 74px) right top / 240px 120px no-repeat,
                linear-gradient(rgba(109, 236, 232, 0.36) 0 2px, transparent 2px 48px) right top / 180px 96px no-repeat,
                linear-gradient(90deg, transparent 0 18px, rgba(109, 236, 232, 0.38) 18px 20px, transparent 20px 74px) left bottom / 240px 120px no-repeat,
                linear-gradient(0deg, rgba(109, 236, 232, 0.34) 0 2px, transparent 2px 48px) left bottom / 180px 96px no-repeat,
                linear-gradient(270deg, transparent 0 18px, rgba(109, 236, 232, 0.38) 18px 20px, transparent 20px 74px) right bottom / 240px 120px no-repeat,
                linear-gradient(0deg, rgba(109, 236, 232, 0.34) 0 2px, transparent 2px 48px) right bottom / 180px 96px no-repeat;
        }

        a {
            transition: color 160ms ease, border-color 160ms ease, background 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        a:hover {
            color: #fbffff;
        }

        :focus-visible {
            outline: 2px solid rgba(98, 243, 238, 0.9);
            outline-offset: 3px;
        }

        .topbar,
        .page,
        .live-stack {
            position: relative;
            z-index: 1;
        }

        .topbar {
            background: rgba(12, 29, 32, 0.72);
            border-bottom: 1px solid rgba(120, 238, 235, 0.2);
            box-shadow: 0 12px 34px rgba(0, 0, 0, 0.24);
        }

        .nav {
            max-width: 1280px;
            padding: 15px 28px;
        }

        .brand {
            color: #f2ffff;
            text-shadow: 0 0 18px rgba(98, 243, 238, 0.42);
        }

        .page {
            width: min(1280px, calc(100% - 68px));
            margin: 26px auto 38px;
            padding: 30px;
            border: 1px solid rgba(119, 240, 236, 0.32);
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(18, 48, 50, 0.34), rgba(10, 24, 27, 0.1));
            box-shadow: inset 0 0 26px rgba(98, 243, 238, 0.06);
        }

        .page::before,
        .page::after {
            position: absolute;
            width: 116px;
            height: 64px;
            pointer-events: none;
            content: "";
            opacity: 0.72;
        }

        .page::before {
            top: 16px;
            left: 16px;
            border-top: 1px solid rgba(126, 242, 239, 0.52);
            border-left: 1px solid rgba(126, 242, 239, 0.52);
        }

        .page::after {
            right: 16px;
            bottom: 16px;
            border-right: 1px solid rgba(126, 242, 239, 0.52);
            border-bottom: 1px solid rgba(126, 242, 239, 0.52);
        }

        .page-head {
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(124, 238, 235, 0.18);
        }

        .eyebrow {
            color: var(--green);
            text-shadow: 0 0 14px rgba(98, 243, 238, 0.45);
        }

        h1,
        h2,
        h3 {
            color: #f5ffff;
            text-shadow: 0 0 18px rgba(147, 244, 241, 0.12);
        }

        h1 {
            font-size: clamp(1.65rem, 2.4vw, 2.28rem);
        }

        h2 {
            font-size: 1.12rem;
        }

        .muted {
            color: var(--muted);
        }

        .panel,
        .report-card,
        .stat-card,
        .auth-card,
        .toolbar {
            color: var(--ink);
            background: linear-gradient(180deg, rgba(29, 59, 63, 0.74), rgba(12, 29, 32, 0.82));
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .report-card {
            box-shadow: var(--shadow) !important;
        }

        .panel,
        .report-card,
        .stat-card,
        .auth-card {
            position: relative;
        }

        .panel::before,
        .report-card::before,
        .stat-card::before {
            position: absolute;
            top: 0;
            right: 14px;
            left: 14px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(133, 250, 246, 0.74), transparent);
            content: "";
        }

        .panel,
        .auth-card {
            padding: 24px;
        }

        .auth-card {
            max-width: 920px !important;
            min-height: 420px;
            margin: 22px auto !important;
            padding: 28px 30px 28px 338px;
            overflow: hidden;
        }

        .auth-card > * {
            position: relative;
            z-index: 1;
        }

        .auth-card::before {
            position: absolute;
            top: 24px;
            bottom: 24px;
            left: 24px;
            width: 270px;
            border: 1px solid rgba(111, 240, 237, 0.32);
            border-radius: 8px;
            background:
                linear-gradient(90deg, rgba(116, 241, 237, 0.14) 1px, transparent 1px) center / 36px 36px,
                linear-gradient(rgba(116, 241, 237, 0.1) 1px, transparent 1px) center / 36px 36px,
                linear-gradient(135deg, rgba(98, 243, 238, 0.2), rgba(216, 167, 255, 0.08));
            box-shadow: inset 0 0 38px rgba(98, 243, 238, 0.12);
            content: "";
        }

        .auth-card::after {
            position: absolute;
            top: 50%;
            left: 159px;
            z-index: 1;
            width: 116px;
            height: 116px;
            display: grid;
            place-items: center;
            color: var(--green);
            border: 2px solid rgba(121, 245, 242, 0.75);
            border-radius: 8px;
            box-shadow: inset 0 0 28px rgba(98, 243, 238, 0.14), 0 0 26px rgba(98, 243, 238, 0.22);
            clip-path: polygon(50% 0, 92% 24%, 92% 76%, 50% 100%, 8% 76%, 8% 24%);
            content: "LF";
            font-size: 2rem;
            font-weight: 900;
            transform: translate(-50%, -50%);
        }

        .auth-card::after {
            line-height: 1;
        }

        .auth-card h1,
        .auth-card .eyebrow {
            max-width: 500px;
        }

        .notice,
        .error-box {
            background: rgba(12, 33, 35, 0.88);
            border-radius: 8px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .notice {
            color: #dcfffc;
            border-color: rgba(98, 243, 238, 0.42);
        }

        .error-box {
            color: #ffd9d6;
            border-color: rgba(255, 119, 111, 0.5);
        }

        .button,
        .ghost-button,
        .danger-button,
        .nav-button {
            min-height: 38px;
            border-radius: 8px;
            transition: color 160ms ease, background 160ms ease, border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
            white-space: nowrap;
        }

        .button {
            color: #062326;
            background: linear-gradient(180deg, #74fff8, #46d8d4);
            border-color: rgba(151, 255, 251, 0.74);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.46), 0 0 18px rgba(77, 232, 227, 0.35);
        }

        .ghost-button,
        .nav-button {
            color: #d8f7f6;
            background: rgba(13, 34, 37, 0.62);
            border-color: rgba(126, 235, 232, 0.28);
            box-shadow: inset 0 0 14px rgba(96, 229, 224, 0.06);
        }

        .danger-button {
            color: #fff5f3;
            background: linear-gradient(180deg, #e45f59, #a73538);
            border-color: rgba(255, 146, 138, 0.48);
            box-shadow: 0 0 16px rgba(255, 119, 111, 0.22);
        }

        .button:hover,
        .ghost-button:hover,
        .danger-button:hover,
        .nav-button:hover {
            transform: translateY(-1px);
        }

        .ghost-button:hover,
        .nav-button:hover {
            color: #f7ffff;
            background: rgba(37, 78, 82, 0.74);
            border-color: rgba(124, 244, 241, 0.52);
            box-shadow: 0 0 16px rgba(98, 243, 238, 0.16);
        }

        .stats-grid {
            grid-template-columns: repeat(4, minmax(138px, 1fr));
            gap: 14px;
        }

        .stat-card {
            min-height: 92px;
            padding: 16px;
        }

        .stat-card span {
            color: var(--muted);
        }

        .stat-card strong {
            color: #fbffff;
            text-shadow: 0 0 18px rgba(98, 243, 238, 0.38);
        }

        .toolbar {
            padding: 12px;
        }

        .field label,
        .radio-label {
            color: #b8cccf;
        }

        .input,
        .textarea,
        .select {
            color: #efffff;
            background: rgba(6, 19, 22, 0.62);
            border: 1px solid rgba(126, 229, 226, 0.24);
            border-radius: 8px;
            box-shadow: inset 0 0 16px rgba(0, 0, 0, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .input::placeholder,
        .textarea::placeholder {
            color: #78969a;
        }

        .input:focus,
        .textarea:focus,
        .select:focus {
            border-color: rgba(98, 243, 238, 0.74);
            box-shadow: inset 0 0 16px rgba(0, 0, 0, 0.28), 0 0 0 3px rgba(98, 243, 238, 0.12);
            outline: none;
        }

        .select option {
            color: #efffff;
            background: #12292c;
        }

        .segmented {
            gap: 8px;
        }

        .segment span {
            color: #d8f7f6;
            background: rgba(13, 34, 37, 0.62);
            border-color: rgba(126, 235, 232, 0.28);
            border-radius: 8px;
        }

        .segment input:checked + span {
            color: #062326;
            background: linear-gradient(180deg, #74fff8, #46d8d4);
            border-color: rgba(151, 255, 251, 0.74);
            box-shadow: 0 0 18px rgba(77, 232, 227, 0.3);
        }

        .report-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .report-card {
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }

        .report-photo,
        .photo-placeholder {
            background: linear-gradient(135deg, rgba(98, 243, 238, 0.18), rgba(247, 197, 95, 0.12));
            border-bottom: 1px solid rgba(126, 235, 232, 0.22);
        }

        .photo-placeholder {
            color: var(--green);
            text-shadow: 0 0 20px rgba(98, 243, 238, 0.44);
        }

        .report-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 15px;
        }

        .report-body p:last-child {
            margin-bottom: 0;
        }

        .badge {
            min-height: 24px;
            color: #eaffff;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 999px;
        }

        .badge-lost,
        .badge-rejected,
        .badge-blocked {
            color: #ffd7d4;
            background: var(--red-soft);
            border-color: rgba(255, 119, 111, 0.3);
        }

        .badge-found,
        .badge-approved,
        .badge-claimed,
        .badge-closed,
        .badge-archived {
            color: #dffffd;
            background: var(--green-soft);
            border-color: rgba(98, 243, 238, 0.32);
        }

        .badge-pending {
            color: #ffe8b5;
            background: var(--amber-soft);
            border-color: rgba(247, 197, 95, 0.32);
        }

        .badge-category {
            color: #dcefff;
            background: var(--blue-soft);
            border-color: rgba(143, 200, 255, 0.3);
        }

        .badge-admin {
            color: #f2dcff;
            background: var(--violet-soft);
            border-color: rgba(216, 167, 255, 0.32);
        }

        .empty-state {
            color: var(--ink);
        }

        .detail-list div {
            background: rgba(5, 18, 21, 0.44);
            border-color: rgba(123, 232, 229, 0.2);
            border-radius: 8px;
        }

        .detail-list dt {
            color: var(--muted);
        }

        .detail-list dd {
            color: #f6ffff;
        }

        .admin-row {
            gap: 20px;
        }

        .admin-actions {
            padding-left: 12px;
            border-left: 1px solid rgba(126, 235, 232, 0.16);
        }

        .status-filters {
            padding: 10px;
            background: rgba(12, 29, 32, 0.42);
            border: 1px solid rgba(126, 235, 232, 0.16);
            border-radius: 8px;
        }

        .pager span {
            color: var(--muted);
        }

        .pager span.ghost-button {
            opacity: 0.55;
        }

        .live-notification {
            color: var(--ink);
            background: rgba(13, 31, 34, 0.9);
            border-color: rgba(117, 237, 235, 0.28);
            border-left-color: var(--green);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .live-notification a {
            color: var(--green);
        }

        @media (max-width: 900px) {
            .page {
                width: min(100% - 32px, 760px);
                padding: 22px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .admin-actions {
                padding-left: 0;
                border-left: 0;
            }
        }

        @media (max-width: 760px) {
            .auth-card {
                min-height: 0;
                padding: 168px 22px 22px !important;
            }

            .auth-card::before {
                top: 22px;
                right: 22px;
                bottom: auto;
                left: 22px;
                width: auto;
                height: 116px;
            }

            .auth-card::after {
                top: 80px;
                left: 50%;
                width: 74px;
                height: 74px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 640px) {
            body::before {
                inset: 12px;
                border-radius: 12px;
            }

            body::after {
                inset: 18px;
                opacity: 0.24;
            }

            .nav {
                padding: 14px 18px;
            }

            .page {
                width: calc(100% - 24px);
                margin-top: 18px;
                padding: 18px;
                border-radius: 12px;
            }

            .page-head {
                align-items: stretch;
            }

            .stats-grid,
            .toolbar,
            .report-grid,
            .form-grid,
            .detail-list {
                grid-template-columns: 1fr;
            }

            .button,
            .ghost-button,
            .danger-button,
            .nav-button {
                width: 100%;
                white-space: normal;
            }
        }

        /* Mint campus registry theme */
        :root {
            --ink: #17342c;
            --muted: #5d776e;
            --line: rgba(21, 122, 78, 0.18);
            --paper: rgba(255, 255, 255, 0.82);
            --wash: #e9f8ef;
            --green: #08733f;
            --green-soft: #d9f4e4;
            --blue: #1d6b63;
            --blue-soft: #dff4ef;
            --amber: #b78212;
            --amber-soft: #fff4cf;
            --red: #b93835;
            --red-soft: #fde5e1;
            --violet: #5b6f9f;
            --violet-soft: #e8eefc;
            --shadow: 0 18px 38px rgba(28, 83, 56, 0.14), 0 4px 12px rgba(16, 72, 46, 0.08);
        }

        html {
            background: #eaf8f0;
        }

        body {
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 16%, rgba(18, 130, 76, 0.15), transparent 28%),
                radial-gradient(circle at 78% 26%, rgba(255, 255, 255, 0.86), transparent 24%),
                linear-gradient(90deg, rgba(15, 126, 73, 0.09) 1px, transparent 1px) left top / 72px 72px,
                linear-gradient(rgba(15, 126, 73, 0.07) 1px, transparent 1px) left top / 72px 72px,
                linear-gradient(135deg, #d6f3e2 0%, #f7fbf8 46%, #dff3e8 100%);
        }

        body::before {
            inset: 18px 26px;
            z-index: 0;
            border: 1px solid rgba(14, 118, 67, 0.2);
            border-radius: 0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72);
        }

        body::after {
            inset: 0;
            z-index: 0;
            opacity: 0.38;
            background:
                linear-gradient(90deg, transparent 0 26px, rgba(9, 111, 61, 0.28) 26px 28px, transparent 28px 78px) left 28px top 98px / 210px 130px no-repeat,
                linear-gradient(rgba(9, 111, 61, 0.18) 0 2px, transparent 2px 54px) left 28px top 98px / 170px 108px no-repeat,
                linear-gradient(270deg, transparent 0 26px, rgba(9, 111, 61, 0.28) 26px 28px, transparent 28px 78px) right 28px top 98px / 210px 130px no-repeat,
                linear-gradient(rgba(9, 111, 61, 0.18) 0 2px, transparent 2px 54px) right 28px top 98px / 170px 108px no-repeat,
                linear-gradient(90deg, transparent 0 26px, rgba(9, 111, 61, 0.22) 26px 28px, transparent 28px 78px) left 28px bottom 40px / 210px 130px no-repeat,
                linear-gradient(270deg, transparent 0 26px, rgba(9, 111, 61, 0.22) 26px 28px, transparent 28px 78px) right 28px bottom 40px / 210px 130px no-repeat;
        }

        .topbar {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(228, 248, 237, 0.88));
            border-bottom: 3px solid rgba(8, 115, 63, 0.72);
            box-shadow: 0 10px 24px rgba(20, 93, 55, 0.12);
        }

        .nav {
            max-width: 1160px;
            padding: 10px 22px;
        }

        .brand {
            gap: 12px;
            color: #17342c;
            text-shadow: none;
        }

        .brand-title {
            color: #17342c;
            font-size: clamp(1rem, 2vw, 1.28rem);
            font-weight: 900;
            line-height: 1.1;
        }

        .nav-links {
            gap: 7px;
        }

        .page {
            width: min(1120px, calc(100% - 44px));
            margin-top: 22px;
            padding: 22px;
            border: 1px solid rgba(18, 127, 72, 0.16);
            border-radius: 0;
            background: rgba(246, 252, 248, 0.66);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72);
        }

        .page::before,
        .page::after {
            border-color: rgba(10, 113, 63, 0.2);
        }

        .page-head {
            align-items: center;
            margin-bottom: 16px;
            padding: 0 0 10px;
            border-bottom: 2px solid rgba(8, 115, 63, 0.16);
        }

        .eyebrow {
            color: #0b7040;
            font-size: 0.82rem;
            text-shadow: none;
        }

        h1,
        h2,
        h3 {
            color: #17342c;
            text-shadow: none;
        }

        h1 {
            margin-bottom: 4px;
            font-size: clamp(1.45rem, 2.2vw, 2rem);
            font-weight: 900;
        }

        h2 {
            font-size: 1.05rem;
        }

        .muted {
            color: var(--muted);
        }

        .panel,
        .report-card,
        .stat-card,
        .auth-card,
        .toolbar {
            color: var(--ink);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(231, 248, 238, 0.86));
            border: 1px solid rgba(19, 122, 72, 0.18);
            border-radius: 8px;
            box-shadow: var(--shadow) !important;
            backdrop-filter: blur(10px);
        }

        .panel::before,
        .report-card::before,
        .stat-card::before {
            right: 12px;
            left: 12px;
            background: linear-gradient(90deg, transparent, rgba(8, 115, 63, 0.45), transparent);
        }

        .panel,
        .auth-card {
            padding: 18px;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat-card {
            min-height: 78px;
            padding: 14px 16px;
        }

        .stat-card span {
            color: #42695a;
            font-size: 0.78rem;
            text-transform: uppercase;
        }

        .stat-card strong {
            color: #0b6b3f;
            font-size: 1.8rem;
            text-shadow: none;
        }

        .toolbar {
            align-items: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.72);
        }

        .input,
        .textarea,
        .select {
            color: #17342c;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(24, 117, 73, 0.16);
            border-radius: 7px;
            box-shadow: inset 0 1px 3px rgba(21, 83, 52, 0.06);
        }

        .input::placeholder,
        .textarea::placeholder {
            color: #7a9188;
        }

        .input:focus,
        .textarea:focus,
        .select:focus {
            border-color: rgba(8, 115, 63, 0.62);
            box-shadow: 0 0 0 3px rgba(8, 115, 63, 0.12);
        }

        .select option {
            color: #17342c;
            background: #f7fbf8;
        }

        .field label,
        .radio-label {
            color: #0b7040;
        }

        .segment span {
            color: #0b5e37;
            background: #e7f7ee;
            border-color: rgba(8, 115, 63, 0.24);
            border-radius: 7px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.68);
        }

        .segment input:checked + span {
            color: #fff;
            background: linear-gradient(180deg, #1aa866, #08733f);
            border-color: #08733f;
            box-shadow: 0 8px 16px rgba(8, 115, 63, 0.2);
        }

        .button,
        .ghost-button,
        .danger-button,
        .nav-button {
            min-height: 36px;
            border-radius: 999px;
            box-shadow: none;
        }

        .button {
            color: #fff;
            background: linear-gradient(180deg, #15945a, #08733f);
            border-color: #08733f;
            box-shadow: 0 8px 14px rgba(8, 115, 63, 0.22);
        }

        .ghost-button,
        .nav-button {
            color: #0b5e37;
            background: rgba(255, 255, 255, 0.76);
            border-color: rgba(8, 115, 63, 0.22);
        }

        .danger-button {
            color: #fff;
            background: linear-gradient(180deg, #d15d52, #ad3934);
            border-color: #ad3934;
        }

        .button:hover,
        .ghost-button:hover,
        .danger-button:hover,
        .nav-button:hover {
            transform: translateY(-1px);
        }

        .ghost-button:hover,
        .nav-button:hover {
            color: #073f25;
            background: #d9f4e4;
            border-color: rgba(8, 115, 63, 0.42);
            box-shadow: none;
        }

        .report-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .report-card {
            overflow: hidden;
            background: rgba(255, 255, 255, 0.86);
        }

        .report-photo,
        .photo-placeholder {
            aspect-ratio: 16 / 10;
            background:
                linear-gradient(135deg, rgba(8, 115, 63, 0.08), rgba(183, 130, 18, 0.1)),
                #eef8f2;
            border-bottom: 1px solid rgba(8, 115, 63, 0.14);
        }

        .photo-placeholder {
            color: rgba(8, 115, 63, 0.34);
            font-size: 2.6rem;
            text-shadow: none;
        }

        .report-body {
            padding: 12px 13px 14px;
        }

        .report-meta {
            gap: 6px;
            margin-bottom: 8px;
        }

        .badge {
            min-height: 23px;
            color: #1d4436;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(8, 115, 63, 0.14);
        }

        .badge-lost,
        .badge-rejected,
        .badge-blocked {
            color: #9e2e2c;
            background: var(--red-soft);
            border-color: rgba(185, 56, 53, 0.18);
        }

        .badge-found,
        .badge-approved,
        .badge-claimed,
        .badge-closed,
        .badge-archived {
            color: #075d35;
            background: var(--green-soft);
            border-color: rgba(8, 115, 63, 0.18);
        }

        .badge-pending {
            color: #8a5d0b;
            background: var(--amber-soft);
            border-color: rgba(183, 130, 18, 0.2);
        }

        .badge-category {
            color: #165d56;
            background: var(--blue-soft);
            border-color: rgba(29, 107, 99, 0.18);
        }

        .badge-admin {
            color: #40527d;
            background: var(--violet-soft);
            border-color: rgba(91, 111, 159, 0.2);
        }

        .detail-list div {
            background: rgba(255, 255, 255, 0.68);
            border-color: rgba(8, 115, 63, 0.14);
        }

        .detail-list dt {
            color: #5d776e;
        }

        .detail-list dd {
            color: #17342c;
        }

        .status-filters {
            background: rgba(255, 255, 255, 0.62);
            border-color: rgba(8, 115, 63, 0.14);
        }

        .admin-actions {
            border-left-color: rgba(8, 115, 63, 0.14);
        }

        .auth-card {
            max-width: 820px !important;
            min-height: 360px;
            padding: 28px 300px 28px 30px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.93), rgba(225, 247, 235, 0.9));
        }

        .auth-card::before {
            top: 28px;
            right: 28px;
            bottom: 28px;
            left: auto;
            width: 220px;
            border-color: rgba(8, 115, 63, 0.18);
            background:
                linear-gradient(135deg, rgba(8, 115, 63, 0.14), rgba(255, 255, 255, 0.62)),
                radial-gradient(circle at 50% 44%, rgba(183, 130, 18, 0.22), transparent 32%);
            box-shadow: inset 0 0 38px rgba(8, 115, 63, 0.08);
        }

        .auth-card::after {
            display: none;
            content: "";
        }

        .live-notification {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.94);
            border-color: rgba(8, 115, 63, 0.18);
            border-left-color: #08733f;
            box-shadow: var(--shadow);
        }

        .live-notification a {
            color: #08733f;
        }

        @media (max-width: 760px) {
            .brand-title {
                font-size: 0.98rem;
            }

            .auth-card {
                padding: 160px 22px 22px !important;
            }

            .auth-card::before {
                top: 22px;
                right: 22px;
                left: 22px;
                width: auto;
                height: 108px;
            }

            .auth-card::after {
                display: none;
            }
        }

        @media (max-width: 640px) {
            body::before {
                inset: 10px;
            }

            .page {
                width: calc(100% - 22px);
                padding: 16px;
            }

            .nav {
                padding: 10px 16px;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <nav class="nav">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-title">{{ config('app.name', 'ReLink: A Web-Based System for Lost Item Reporting and Recovery') }}</span>
            </a>
            <div class="nav-links">
                <a class="nav-button" href="{{ route('home') }}">Public Board</a>
                @auth
                    @if (auth()->user()->isAdmin())
                        <a class="nav-button" href="{{ route('admin.dashboard') }}">Admin</a>
                    @else
                        <a class="nav-button" href="{{ route('student.dashboard') }}">Student</a>
                    @endif
                    <form class="inline-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="nav-button" type="submit">Logout</button>
                    </form>
                @else
                    <a class="nav-button" href="{{ route('login') }}">Login</a>
                    <a class="button" href="{{ route('register') }}">Sign Up</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="page">
        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @auth
        @if (auth()->user()->isStudent())
            <div
                id="realtime-notifications"
                class="live-stack"
                data-endpoint="{{ route('student.notifications.unread') }}"
                aria-live="polite"
                aria-atomic="false"
            ></div>

            <script>
                (() => {
                    const stack = document.getElementById('realtime-notifications');
                    const endpoint = stack?.dataset.endpoint;

                    if (!stack || !endpoint) {
                        return;
                    }

                    const addNotification = (notification) => {
                        const card = document.createElement('article');
                        card.className = 'live-notification';

                        const title = document.createElement('strong');
                        title.textContent = notification.title || 'Notification';

                        const message = document.createElement('p');
                        message.textContent = notification.message || '';

                        const link = document.createElement('a');
                        link.href = notification.url || "{{ route('student.dashboard') }}";
                        link.textContent = 'View details';

                        card.append(title, message, link);
                        stack.prepend(card);

                        while (stack.children.length > 4) {
                            stack.lastElementChild.remove();
                        }

                        setTimeout(() => card.remove(), 18000);
                    };

                    const pollNotifications = async () => {
                        try {
                            const response = await fetch(endpoint, {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                return;
                            }

                            const data = await response.json();
                            (data.notifications || []).forEach(addNotification);
                        } catch (error) {
                        }
                    };

                    pollNotifications();
                    setInterval(pollNotifications, 5000);
                })();
            </script>
        @endif
    @endauth
</body>
</html>
