import React, { createContext, useState } from "react";

const initialState = {
    unreadCount: 0
};

const AppContext = createContext(initialState);
let reducer = (state, action) => {
    switch (action.type) {
        case "reset":
            return initialState;
        case "increment":
            return { ...state, unreadCount: state.unreadCount + 1 };
        case "decrement":
            return { ...state, unreadCount: state.unreadCount - 1 };
        case "setUnreadCount":
            return { ...state, unreadCount: action.payload };
    }
};

function AppContextProvider(props) {
    let [state, dispatch] = React.useReducer(reducer, initialState);
    let value = { state, dispatch };

    return (
        <AppContext.Provider value={value}>
            {props.children}
        </AppContext.Provider>
    );
}
const AppContextConsumer = AppContext.Consumer;

export { AppContext, AppContextProvider, AppContextConsumer };
