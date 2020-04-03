import React, { useState, useEffect, useContext } from "react";
import "./Notification.css";

const Notification = ({ notification, readNotification }) => {
    const { _id, title, desc, link, photoUrl, created, isRead } = notification;
    const [read, setRead] = useState(isRead);

    useEffect(() => {}, [read, setRead]);

    return (
        <div className="notifications__notifi-box__item">
            <a
                className={
                    !read ? "notifications-unread" : "notifications-seen"
                }
                href={link}
                onClick={e => {
                    if (!read) {
                        setRead(true);
                        readNotification(_id);
                    }
                }}
            >
                <div className="notifications__notifi-box__item__img">
                    <img src={photoUrl} />
                </div>
                <div className="notifications__notifi-box__item__text">
                    <h4>{title}</h4>
                    <p>{desc}</p>
                    <span className="notify-time">{created}</span>
                </div>
                <div className="notifications__notifi-box__item__corner-circle active">
                    <div></div>
                </div>
            </a>
        </div>
    );
};

export default Notification;
