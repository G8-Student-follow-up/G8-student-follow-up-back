import express from "express";
import http from "http";
import { Server } from "socket.io";

const app = express();
const server = http.createServer(app);

app.use(express.json());

const io = new Server(server, {
     cors: {
        origin: [
            "http://localhost:5173",
            "http://localhost:5174"
        ],
        methods: ["GET", "POST"],
    },
});

/**
 * Track which users are connected (userId -> Set<socketId>)
 */
const userSockets = new Map();

io.on("connection", (socket) => {
    console.log("User connected: ", socket.id);

    // ── User registration ──
    socket.on("register-user", (userId) => {
        if (!userSockets.has(userId)) {
            userSockets.set(userId, new Set());
        }
        userSockets.get(userId).add(socket.id);
        socket.data.userId = userId;
        console.log(`User ${userId} registered with socket ${socket.id}`);
    });

    // ── Card room management ──
    socket.on("join-card", (cardId) => {
        socket.join(`card-${cardId}`);
        console.log(`${socket.id} joined card-${cardId}`);
    });

    socket.on("leave-card", (cardId) => {
        socket.leave(`card-${cardId}`);
    });

    // ── Comment events ──
    socket.on("new-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-created", data.comment);
    });

    socket.on("update-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-updated", data.comment);
    });

    socket.on("delete-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-deleted", { commentId: data.commentId });
    });

    // ── Attachment events ──
    socket.on("new-attachment", (data) => {
        socket.to(`card-${data.cardId}`).emit("attachment-created", {
            cardId: data.cardId,
            file_name: data.attachment?.file_name || "file"
        });
    });

    socket.on("delete-attachment", (data) => {
        socket.to(`card-${data.cardId}`).emit("attachment-deleted", { attachmentId: data.attachmentId });
    });

    // ── Disconnect ──
    socket.on("disconnect", () => {
        console.log("Disconnected:", socket.id);
        const userId = socket.data.userId;
        if (userId && userSockets.has(userId)) {
            userSockets.get(userId).delete(socket.id);
            if (userSockets.get(userId).size === 0) {
                userSockets.delete(userId);
            }
        }
    });
});

/**
 * Emit a notification to a specific user across all their connected sockets.
 */
function emitToUser(userId, event, data) {
    const sockets = userSockets.get(String(userId));
    if (sockets) {
        sockets.forEach((socketId) => {
            io.to(socketId).emit(event, data);
        });
    }
}

/**
 * HTTP endpoint for Laravel backend to push a real-time notification
 * to a specific user. The Laravel app will POST to this endpoint
 * after creating a notification in the DB.
 *
 * POST /emit
 * Body: { userId, notification: { id, type, title, message, ... } }
 */
app.post("/emit", (req, res) => {
    const { userId, notification } = req.body;

    if (!userId || !notification) {
        return res.status(400).json({ error: "Missing userId or notification" });
    }

    emitToUser(userId, "notification", notification);

    // Also emit specific type events for backward compatibility
    if (notification.type === "invite") {
        emitToUser(userId, "invitation-received", notification);
    }

    res.json({ sent: true });
});

// Export for testing
export { io, emitToUser };

server.listen(3001, () => {
    console.log("Socket running on port 3001");
});
