import React from "react";

interface AvatarProps {
    imageSrc: string;
    altText?: string;
    size?: number; // padrão 100px
}

const Avatar: React.FC<AvatarProps> = ({ imageSrc, altText = "Avatar", size = 100 }) => {
    return (
        <div style={{ textAlign: "center", marginBottom: "20px" }}>
            <img
            src={imageSrc}
            alt={altText}
            style={{
                width: size,
                height: size,
                borderRadius: "50%",
                objectFit: "cover",
                border: "2px solid #0c1a35",
                boxShadow: `
                    0 0 8px rgba(12, 26, 53, 0.4),
                    0 0 16px rgba(12, 26, 53, 0.3),
                    0 0 24px rgba(12, 26, 53, 0.2)
                `,
                transition: "box-shadow 0.4s ease-in-out"
            }}
            onMouseEnter={(e) => {
                (e.currentTarget.style.boxShadow =
                    "0 0 12px rgba(12, 26, 53, 0.6), 0 0 20px rgba(12, 26, 53, 0.5)");
            }}
            onMouseLeave={(e) => {
                (e.currentTarget.style.boxShadow =
                    "0 0 8px rgba(12, 26, 53, 0.4), 0 0 16px rgba(12, 26, 53, 0.3), 0 0 24px rgba(12, 26, 53, 0.2)");
            }}
            />
        </div>
    );
};

export default Avatar;