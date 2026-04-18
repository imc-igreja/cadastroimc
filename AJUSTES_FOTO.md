# Ajustes no Posicionamento da Foto

## Alterações Realizadas

### 1. **Melhorias no Script Python (gerar_carteirinha.py)**

#### Antes:
```python
c.drawImage(img, 293, 247, width=46, height=33,
            preserveAspectRatio=True, mask='auto')
```

#### Depois:
- **Dimensões ajustadas**: Altura aumentada de 33pt para 55pt para melhor proporção
- **Posição Y ajustada**: De 247pt para 240pt para centralização correta
- **Sistema de crop inteligente**: Implementado algoritmo que:
  - Calcula o aspect ratio da foto original
  - Compara com o aspect ratio da área de destino
  - Ajusta automaticamente para preencher toda a área
  - Centraliza a imagem com crop nas bordas se necessário

### 2. **Como Funciona o Novo Sistema**

```python
# Dimensões da área da foto
target_width = 46pt
target_height = 55pt

# Posição na carteirinha
foto_x = 293pt
foto_y = 240pt
```

**Lógica de Ajuste:**
- Se a foto for mais **larga** que a área → ajusta pela altura e corta as laterais
- Se a foto for mais **alta** que a área → ajusta pela largura e corta topo/base
- Sempre mantém a foto **centralizada** na área disponível

### 3. **Benefícios**

✓ **Preenchimento completo** - Não deixa espaços vazios
✓ **Proporção mantida** - Não distorce a imagem
✓ **Centralização automática** - Sempre mostra a parte central da foto
✓ **Compatível com qualquer formato** - Funciona com fotos verticais, horizontais ou quadradas

## Como Testar

### Opção 1: Usar o Script de Teste
```bash
# 1. Coloque uma foto de teste em uploads/foto_teste.jpg
# 2. Acesse no navegador:
http://localhost/testar_foto.php
```

### Opção 2: Cadastrar um Novo Ministro
```bash
# 1. Acesse index.php
# 2. Preencha o formulário
# 3. Faça upload da foto
# 4. Gere a carteirinha
```

## Coordenadas de Referência

### Área da Foto na Carteirinha:
- **X**: 293pt (da esquerda)
- **Y**: 240pt (de baixo)
- **Largura**: 46pt
- **Altura**: 55pt

### Sistema de Coordenadas:
- Origem: Canto inferior esquerdo (padrão PDF)
- Unidade: Pontos (pt) - 1pt ≈ 0.35mm

## Troubleshooting

### Foto não aparece:
1. Verifique se o arquivo existe no caminho correto
2. Confirme permissões de leitura da pasta uploads/
3. Verifique se PIL (Pillow) está instalado: `pip install Pillow`

### Foto aparece cortada demais:
- Ajuste `target_height` no código (linha ~120)
- Valores maiores = mais área visível verticalmente

### Foto aparece deslocada:
- Ajuste `foto_x` e `foto_y` (linhas ~138-139)
- Valores maiores = move para direita/cima

## Dependências Necessárias

```bash
pip install reportlab pypdf Pillow
```

## Arquivos Modificados

- ✏️ `gerar_carteirinha.py` - Lógica de posicionamento da foto
- ➕ `testar_foto.php` - Script de teste
- ➕ `AJUSTES_FOTO.md` - Esta documentação
