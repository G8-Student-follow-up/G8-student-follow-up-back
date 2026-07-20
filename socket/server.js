import express from "express";
import http from "http";
import { Server } from "socket.io";

const app = express();

const server = http.createServer(app);

const io = new Server(server, {
     cors: {
        origin: [
            "http://localhost:5173",
            "http://localhost:5174"
        ],
        methods: ["GET", "POST"],
    },
});

io.on("connection", (socket) => {
    console.log("User conneted: ", socket.id);

    socket.on("join-card", (cardId) => {
        socket.join(`card-${cardId}`);

        console.log(`${socket.id} joined card-${cardId}`);
    });

    socket.on("leave-card", (cardId) => {
        socket.leave(`card-${cardId}`);
    })

    socket.on("new-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-created", data.comment);
    });

    socket.on("update-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-updated", data.comment);
    })

    socket.on("delete-comment", (data) => {
        socket.to(`card-${data.cardId}`).emit("comment-deleted", { commentId: data.commentId })
    })

    socket.on("disconnect", () => { console.log("Disconnected:", socket.id); });
});

server.listen(3001, () => { console.log("Socket running on port 3001"); });
