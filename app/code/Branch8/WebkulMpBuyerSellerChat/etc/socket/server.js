const roomUsers = {};
// id -> socketId map // seperate app and web
const webActiveUsers = new Map();
const appActiveUsers = new Map();

var siofu = require('socketio-file-upload'),
    fs = require('fs'),
    app = require('http').createServer(function (req, res) {
        const myURL = new URL(req.url, `http://${req.headers.host}`);
        if (req.method === 'POST' && myURL.pathname === '/foreLogout') {
            let body = '';
            req.on('data', chunk => {
                body += chunk.toString();
            });
            req.on('end', () => {
                if (req.headers['x-api-key'] !== 'HOTAIFKE4PGN6C0') {
                    res.writeHead(401);
                    res.end("Invalid API Key");
                    return;
                }
                // If JSON
                const data = JSON.parse(body);
                const {user_id, token, device_type} = data;
                //forLogoutAnotherUsers(user_id, device_type, token);
                res.writeHead(200, {'Content-Type': 'application/json'});
                res.end(JSON.stringify({status: 'ok'}));
            });
        } else {
            res.writeHead(200, {'Content-Type': 'text/plain'});
            res.end('okay!');
        }
    }), io = require('socket.io')(app, {
        allowEIO3: true
    });

/**
 *
 * @param socket
 * @param user_id
 * @param deviceType
 * @param newToken
 * @param forced
 * @returns {Promise<void>}
 */
async function forLogoutAnotherUsers(socket, user_id, deviceType, newToken, forced = false) {
    const previousSocketID = deviceType === 'web' ? webActiveUsers.get(user_id) : appActiveUsers.get(user_id);
    console.log("##############BEGIN FORCE LOGGOUT################");
    if (previousSocketID) {
        console.log("##############FOUND PREVIOUS SOCKET INFOR################");
        console.log("PREVIOUS SOCKET ID:", previousSocketID);
        const previousInstance = io.of("/").sockets.get(previousSocketID);
        const data = {
            message: "You have been logged out from another device",
            newToken: newToken,
            forced: forced
        };
        io.to(previousSocketID).emit("customer:received:force-logout", data);
        if (
            previousSocketID &&
            previousSocketID !== socket.id
        ) {
            const oldSocket = io.of("/").sockets.get(previousSocketID);
            if (oldSocket) {
                oldSocket.disconnect(true);
            }
        }
        /*const instance = io.of("/").sockets.get(previousSocketID);*/
        /* if (instance) {
             instance.disconnect(true);
         }*/
        console.log({
            user_id, deviceType, newToken, previousSocketID,
            previousInstance
        })
    } else {
        console.log("##############NO PREVIOUS LOG IN SESSION  FOUND################");
    }
    console.log("##############END FORCE LOGGOUT################");
}

app.listen(8080, function () {
    console.log('listening');
});

io.on('connection', function (socket) {
    /**********************************************
     CUSTOMER
     ***********************************************/
    // single device login
    socket.on('customer:start:newSession', async function (details) {
        try {
            const {customerId, login_token, device_type} = details, socketId = socket.id;
            console.log('New customer session started:', {customerId, login_token, socketId, device_type});
            await forLogoutAnotherUsers(socket, customerId, device_type, login_token);
            //device_type === 'web' ? webActiveUsers.set(customerId, socket.id) : appActiveUsers.set(customerId, socket.id);
            if (device_type === 'web') {
                webActiveUsers.set(customerId, socket.id);
                console.log('NewWebSocketID:', webActiveUsers.get(customerId));
            } else {
                appActiveUsers.set(customerId, socket.id);
                console.log('NewAppSocketID:', appActiveUsers.get(customerId));
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }

    });

    socket.on("customer:disconnectSession", () => {
        for (const [u, s] of webActiveUsers.entries()) {
            if (s === socket.id) webActiveUsers.delete(u);
        }
        for (const [u, s] of webActiveUsers.entries()) {
            if (s === socket.id) appActiveUsers.delete(u);
        }
    });

    socket.on("disconnect", () => {
        for (const [u, s] of webActiveUsers.entries()) {
            if (s === socket.id) webActiveUsers.delete(u);
        }
        for (const [u, s] of appActiveUsers.entries()) {
            if (s === socket.id) appActiveUsers.delete(u);
        }
    });

    // end single device login
    socket.on('newCustomerConneted', function (details) {
        var index = details.customerData.uniqueId;
        roomUsers[index] = socket.id;
        Object.keys(roomUsers).forEach(function (key, value) {
            if (key === details.receiverUniqueId) {
                let receiverSocketId = roomUsers[key];
                socket.broadcast.to(receiverSocketId).emit('seller:received:refresh-seller-chat-list', details);
            }
        });
    });


    socket.on('customer:send:new-message', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key, value) {
                    console.log({
                        roomUsers: roomUsers,
                        data: data
                    })
                    if (key === data.receiverUniqueId) {
                        let receiverSocketId = roomUsers[key];

                        socket.broadcast.to(receiverSocketId).emit('seller:received:new-message', data);
                    }
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }

    });
    socket.on('customer:send:customer-profile-change', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit('seller:received:customer-profile-change',
                                data.profileData
                            );
                        }
                    })
                })
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    });

    socket.on('customer:send:start-new-conversation', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key) {
                    if (key === data.receiverId) {
                        const receiverSocketId = roomUsers[key];
                        socket.broadcast.to(receiverSocketId).emit(
                            'seller:received:new-conversation',
                            {
                                'newConversation': data.newConversation,
                                'newMessage': data.newMessage ? data.newMessage : ''
                            }
                        );
                    }
                })
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }

    });
    socket.on('customer:send:customer-status-change', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit(
                                'seller:received:customer-status-change',
                                {
                                    status: data.status,
                                    uniqueId: data.uniqueID
                                }
                            );
                        }
                    })
                })
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    })

    socket.on('customer:send:block-event', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key, value) {
                    if (key === data.customerUniqueId) {
                        let receiverSocketId = roomUsers[key];
                        socket.broadcast.to(receiverSocketId).emit('customer blocked by seller', data);
                    }
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    });
    socket.on('customer:send:typing-message', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key, value) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit(
                                'seller:received:typing-message',
                                {
                                    name: data.name,
                                    message: data.message,
                                    conversationUniqueId: data.conversationUniqueId,
                                    uniqueId: receiverId
                                }
                            );
                        }
                    });
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    });
    /**********************************************
     SELlER
     ***********************************************/
    socket.on('newSellerConneted', function (details) {
        var index = details.uniqueId;
        roomUsers[index] = socket.id;

    });
    socket.on('seller:send:seller-profile-change', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit('customer:received:seller-profile-change',
                                data.profileData
                            );
                        }
                    })
                })
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }

    });
    socket.on('seller:send:new-message', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                console.log({
                    roomUsers: roomUsers,
                    data: data
                })
                Object.keys(roomUsers).forEach(function (key, value) {

                    if (key === data.receiverUniqueId) {
                        const receiverSocketId = roomUsers[key];
                        socket.broadcast.to(receiverSocketId).emit('customer:received:new-message', data);
                    }
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }

    });
    socket.on('seller:send:seller-status-change', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key, value) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit(
                                'customer:received:seller-status-change',
                                {
                                    status: data.status,
                                    uniqueId: data.uniqueId
                                }
                            );
                        }
                    });
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    });
    socket.on('seller:send:typing-message', function (data) {
        try {
            if (typeof (data) !== 'undefined') {
                Object.keys(roomUsers).forEach(function (key, value) {
                    Object(data.receiverList).forEach(function (receiverId) {
                        if (key === receiverId) {
                            const receiverSocketId = roomUsers[key];
                            socket.broadcast.to(receiverSocketId).emit(
                                'customer:received:typing-message',
                                {
                                    name: data.name,
                                    message: data.message,
                                    conversationUniqueId: data.conversationUniqueId,
                                    uniqueId: receiverId
                                }
                            );
                        }
                    });
                });
            }
        } catch (e) {
            console.log("ERROR:" + e);
        }
    });

});
