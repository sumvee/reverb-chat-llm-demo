// Live chat for the WebSockets demo. REST for history + sending (ChatController),
// Reverb for delivery. Binds to [data-chat-widget] and finds its own sub-nodes.
//
// Each widget can run on its own Echo connection and act as a signed demo user
// (data-demo-token), which is how the side-by-side view puts two people in one
// browser window.

import axios from 'axios';

axios.defaults.headers.common['X-CSRF-TOKEN'] =
    document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

function bubble(m) {
    const seg = document.createElement('div');
    seg.className = 'msg ' + (m.mine ? 'msg--me' : 'msg--them');
    const parts = [];
    if (!m.mine && m.sender_name) parts.push(`<div class="msg__name">${escapeHtml(m.sender_name)}</div>`);
    parts.push(`<p class="msg__body">${escapeHtml(m.body)}</p>`);
    if (m.time) parts.push(`<div class="msg__time">${escapeHtml(m.time)}</div>`);
    seg.innerHTML = parts.join('');
    return seg;
}

class ChatWidget {
    constructor(root, opts = {}) {
        this.root = root;
        this.echo = opts.echo || window.Echo;
        this.token = opts.token || null;
        this.openWith = root.dataset.openWith ? Number(root.dataset.openWith) : null;

        this.list = root.querySelector('[data-chat-conversations]');
        this.thread = root.querySelector('[data-chat-messages]');
        this.form = root.querySelector('[data-chat-form]');
        this.input = root.querySelector('[data-chat-input]');
        this.presence = root.querySelector('[data-chat-presence]');
        this.title = root.querySelector('[data-chat-title]');
        this.me = null;
        this.active = null;
        this.channel = null;
        this.activeIsBot = false;
        this.typingEl = null;
        this.streamEls = {};

        this.form?.addEventListener('submit', (e) => this.onSubmit(e));
        this.wireStatus();
        this.joinPresence();
        this.loadConversations();
    }

    headers(extra = {}) {
        return this.token ? { 'X-Demo-Token': this.token, ...extra } : { ...extra };
    }

    async loadConversations() {
        const { data } = await axios.get('/chat/conversations', { headers: this.headers() });
        this.me = data.me;

        // Locked single-conversation mode (the side-by-side panes): no list to
        // render; just open the conversation with the chosen person.
        if (!this.list) {
            const c = data.conversations.find((x) => x.other_id === this.openWith) || data.conversations[0];
            if (c) this.open(c);
            return;
        }

        this.list.innerHTML = '';
        let opened = false;
        data.conversations.forEach((c, i) => {
            const a = document.createElement('a');
            a.href = 'javascript:void(0)';
            a.className = 'conv';
            a.dataset.conversationId = c.id;
            a.innerHTML =
                `<span class="conv__dot" data-presence-for="${c.other_id}"></span>` +
                `<span class="conv__name">${escapeHtml(c.name)}</span>` +
                (c.is_bot ? `<span class="conv__bot">AI</span>` : '');
            a.addEventListener('click', () => this.open(c, a));
            this.list.appendChild(a);

            // open the conversation with a preferred person, else the first one
            if ((this.openWith && c.other_id === this.openWith) || (!this.openWith && i === 0)) {
                this.open(c, a);
                opened = true;
            }
        });
        if (!opened && this.list.firstChild) this.list.firstChild.click();
    }

    async open(c, el) {
        this.active = c.id;
        this.activeIsBot = !!c.is_bot;
        this.removeTyping();
        this.streamEls = {};
        if (this.list) this.list.querySelectorAll('.conv').forEach((n) => n.classList.remove('is-active'));
        el?.classList.add('is-active');
        if (this.title) this.title.textContent = c.name;
        if (this.channel) this.echo.leave('conversation.' + this.channel);

        const { data } = await axios.get(`/chat/${c.id}/messages`, { headers: this.headers() });
        this.thread.innerHTML = '';
        data.messages.forEach((m) => this.thread.appendChild(bubble(m)));
        this.scroll();

        this.channel = c.id;
        const ch = this.echo.private('conversation.' + c.id);
        ch.listen('.MessageSent', (e) => {
            if (e.conversation_id !== this.active) return;
            if (e.sender_id === this.me.id) return;   // my own message; already shown optimistically
            this.removeTyping();
            this.thread.appendChild(bubble({ ...e, mine: false }));
            this.scroll();
        });
        ch.listen('.AssistantStream', (e) => this.onStream(e));
    }

    onStream(e) {
        if (e.conversation_id !== this.active) return;
        if (e.phase === 'start') {
            this.removeTyping();
            const seg = bubble({ sender_name: e.sender_name, body: '', time: '', mine: false });
            this.thread.appendChild(seg);
            this.streamEls[e.message_id] = seg.querySelector('.msg__body');
            this.scroll();
        } else if (e.phase === 'token') {
            const p = this.streamEls[e.message_id];
            if (p) { p.textContent += e.delta; this.scroll(); }
        } else if (e.phase === 'done') {
            delete this.streamEls[e.message_id];
        }
    }

    async onSubmit(e) {
        e.preventDefault();
        const body = this.input.value.trim();
        if (!body || !this.active) return;
        this.input.value = '';
        this.thread.appendChild(bubble({ body, time: '', mine: true }));
        this.scroll();
        if (this.activeIsBot) this.showTyping();
        await axios.post(`/chat/${this.active}/send`, { body }, { headers: this.headers() });
    }

    showTyping() {
        if (this.typingEl) return;
        const seg = document.createElement('div');
        seg.className = 'msg msg--them msg--typing';
        seg.innerHTML = '<p class="msg__body">AI Chatbot is thinking...</p>';
        this.typingEl = seg;
        this.thread.appendChild(seg);
        this.scroll();
    }

    removeTyping() {
        if (this.typingEl) { this.typingEl.remove(); this.typingEl = null; }
    }

    joinPresence() {
        this.echo.join('chat')
            .here((users) => this.markOnline(users.map((u) => u.id)))
            .joining((u) => this.markOnline([u.id], true))
            .leaving((u) => this.setDot(u.id, false));
    }

    markOnline(ids, add = false) {
        if (!add) this.root.querySelectorAll('[data-presence-for]').forEach((el) => el.classList.remove('is-online'));
        ids.forEach((id) => this.setDot(id, true));
        if (this.presence) this.presence.textContent = ids.length + ' online';
    }

    setDot(id, online) {
        this.root.querySelectorAll(`[data-presence-for="${id}"]`).forEach((el) => el.classList.toggle('is-online', online));
    }

    wireStatus() {
        const scope = this.root.closest('.split-pane') || this.root;
        const dot = scope.querySelector('[data-ws-status]');
        const label = scope.querySelector('[data-ws-label]');
        const conn = this.echo?.connector?.pusher?.connection;
        if (!conn || (!dot && !label)) return;
        const set = (state) => {
            const live = state === 'connected';
            dot?.classList.toggle('is-live', live);
            if (label) label.textContent = live ? 'connected' : state;
        };
        set(conn.state);
        conn.bind('state_change', (e) => set(e.current));
    }

    scroll() { this.thread.scrollTop = this.thread.scrollHeight; }
}

// Topbar connection indicator for the normal /chat page (outside the widget root).
function wireGlobalStatus() {
    const conn = window.Echo?.connector?.pusher?.connection;
    const dot = document.querySelector('.topbar [data-ws-status]');
    const label = document.querySelector('.topbar [data-ws-label]');
    if (!conn || (!dot && !label)) return;
    const set = (state) => {
        const live = state === 'connected';
        dot?.classList.toggle('is-live', live);
        if (label) label.textContent = live ? 'realtime connected' : state;
    };
    set(conn.state);
    conn.bind('state_change', (e) => set(e.current));
}

window.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) return;
    wireGlobalStatus();
    document.querySelectorAll('[data-chat-widget]').forEach((el) => {
        const token = el.dataset.demoToken;
        const echo = token ? window.makeDemoEcho(token) : window.Echo;
        new ChatWidget(el, { echo, token });
    });
});
