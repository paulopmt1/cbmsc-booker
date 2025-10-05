import React, { useState } from "react";
import "../../styles/LoginPage.css";
import Avatar from "../../components/Avatar";
import type { LoginFormData } from "../../types/loginTypes";
import bombeiro from "../../assets/bombeiro.png";

const LoginPage: React.FC = () => {
  const [formData, setFormData] = useState<LoginFormData>({
    email: "",
    password: "",
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    console.log("Tentando logar com: ", formData);

    // Aqui vai autenticação ou chamda da API...
  };

  return (
    <div
      className="login-container">
      <div
      className="login-card">
        <Avatar imageSrc={bombeiro} altText="bombeiro" size={120} />
        <h2>Login</h2>
        <form onSubmit={handleSubmit} className="login-form">
          <label htmlFor="email">Seu Email</label>
          <input
            type="email"
            name="email"
            placeholder="Digite seu Email"
            value={formData.email}
            onChange={handleChange}
            required
          />
          <label htmlFor="senha">Senha</label>
          <input
            type="password"
            name="password"
            placeholder="Digite sua senha"
            value={formData.password}
            onChange={handleChange}
            required
          />
          <button type="submit">Entrar</button>
        </form>
      </div>
    </div>
  );
};

export default LoginPage;
