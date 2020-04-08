import jwt from "jsonwebtoken";
import Axios from "axios";
import Cookies from "js-cookie";

export async function generateJwtIfExpired() {
    let accessToken = Cookies.get("g-u-at");
    if (accessToken) {
        const { exp } = jwt.decode(accessToken);
        if (new Date().getTime() > exp * 1000) {
            await Axios.get("/api/jwt/token_refresh", {
                withCredentials: true
            }).then(res => {
                if (res.data.status == "success") {
                    accessToken = res.data.tokens.accessToken;
                    const refreshToken = res.data.tokens.refreshToken;
                    const in30Minutes = 1 / 48;
                    const domain = `.${window.location.hostname}`;
                    Cookies.set("g-u-at", accessToken, {
                        expires: in30Minutes,
                        domain: ".girchi.docker.localhost"
                    });
                    Cookies.set("g-u-rt", refreshToken, {
                        expires: in30Minutes,
                        domain: ".girchi.docker.localhost"
                    });
                } else {
                    console.log(res);
                }
            });
        }
    }
    return accessToken;
}
