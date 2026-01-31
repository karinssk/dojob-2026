<style>
    .lbe-workflow {
        --ink: #1c1e22;
        --muted: #5e6572;
        --accent: #d94b24;
        --accent-2: #2b6cb0;
        --panel: #ffffff;
        --soft: #f4f2ef;
        --line: #eadfd6;
        font-family: "Space Grotesk", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    }

    .lbe-workflow .workflow-hero {
        background: linear-gradient(135deg, #f8efe7 0%, #fff6ef 55%, #eef6ff 100%);
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 22px 24px;
        margin-bottom: 18px;
        position: relative;
        overflow: hidden;
    }

    .lbe-workflow .workflow-hero h2 {
        font-size: 22px;
        margin: 0 0 6px 0;
        color: var(--ink);
        letter-spacing: 0.2px;
    }

    .lbe-workflow .workflow-hero p {
        margin: 0;
        color: var(--muted);
    }

    .lbe-workflow .workflow-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 16px;
    }

    .lbe-workflow .step-card {
        grid-column: span 6;
        background: var(--panel);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 6px 18px rgba(20, 20, 20, 0.05);
        position: relative;
    }

    .lbe-workflow .step-card h4 {
        margin: 0 0 6px 0;
        color: var(--ink);
        font-size: 16px;
    }

    .lbe-workflow .step-card p {
        margin: 0;
        color: var(--muted);
        font-size: 14px;
        line-height: 1.5;
    }

    .lbe-workflow .step-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--soft);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        color: var(--accent);
    }

    .lbe-workflow .step-card:nth-child(2n) .step-icon {
        color: var(--accent-2);
    }

    .lbe-workflow .step-number {
        position: absolute;
        top: 14px;
        right: 14px;
        font-weight: 700;
        color: #d5c9bf;
        font-size: 18px;
    }

    .lbe-workflow .flow-rail {
        grid-column: span 12;
        background: #1e1f24;
        color: #f7f3ee;
        border-radius: 16px;
        padding: 18px 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .lbe-workflow .flow-chip {
        background: rgba(255, 255, 255, 0.08);
        padding: 6px 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .lbe-workflow .flow-arrow {
        color: #f3c9b7;
        font-weight: 600;
    }

    .lbe-workflow .mini-hint {
        margin-top: 14px;
        color: var(--muted);
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .lbe-workflow .step-card {
            grid-column: span 12;
        }
    }
</style>

<div class="card-body lbe-workflow">
    <div class="workflow-hero">
        <h2><?php echo app_lang('line_expenses_workflow_title'); ?></h2>
        <p><?php echo app_lang('line_expenses_workflow_subtitle'); ?></p>
    </div>

    <div class="workflow-grid">
        <div class="flow-rail">
            <span class="flow-chip"><i data-feather="message-square" class="icon-14"></i> LINE message</span>
            <span class="flow-arrow">→</span>
            <span class="flow-chip"><i data-feather="filter" class="icon-14"></i> Parse input</span>
            <span class="flow-arrow">→</span>
            <span class="flow-chip"><i data-feather="hash" class="icon-14"></i> Keyword match</span>
            <span class="flow-arrow">→</span>
            <span class="flow-chip"><i data-feather="database" class="icon-14"></i> Create expense</span>
            <span class="flow-arrow">→</span>
            <span class="flow-chip"><i data-feather="send" class="icon-14"></i> Reply</span>
        </div>

        <div class="step-card">
            <div class="step-number">01</div>
            <div class="step-icon"><i data-feather="message-square" class="icon-18"></i></div>
            <h4>Message Received</h4>
            <p>LINE webhook receives a message with date, title keyword, category keyword, description, amount, and project keyword.</p>
        </div>

        <div class="step-card">
            <div class="step-number">02</div>
            <div class="step-icon"><i data-feather="filter" class="icon-18"></i></div>
            <h4>Input Parsing</h4>
            <p>The bot validates the format and extracts fields. Images are stored temporarily if sent before text.</p>
        </div>

        <div class="step-card">
            <div class="step-number">03</div>
            <div class="step-icon"><i data-feather="hash" class="icon-18"></i></div>
            <h4>Keyword Mapping</h4>
            <p>Title, project, and category keywords are matched against your keyword tables for exact mapping.</p>
        </div>

        <div class="step-card">
            <div class="step-number">04</div>
            <div class="step-icon"><i data-feather="briefcase" class="icon-18"></i></div>
            <h4>Project + Client</h4>
            <p>Project keywords resolve to a client and project. Monthly projects auto-append the current month.</p>
        </div>

        <div class="step-card">
            <div class="step-number">05</div>
            <div class="step-icon"><i data-feather="database" class="icon-18"></i></div>
            <h4>Expense Created</h4>
            <p>Expense is saved with VAT and category. Images are attached if available.</p>
        </div>

        <div class="step-card">
            <div class="step-number">06</div>
            <div class="step-icon"><i data-feather="send" class="icon-18"></i></div>
            <h4>Confirmation Reply</h4>
            <p>The bot replies with a summary card for quick review.</p>
        </div>
    </div>

    <div class="mini-hint">
        Tip: Keep keywords short and exact to avoid mismatches. Category keywords are checked before numeric IDs.
    </div>
</div>

<script>
    $(document).ready(function () {
        if (typeof feather !== "undefined") {
            feather.replace();
        }
    });
</script>
