<?php

use Phoundation\Seo\Seo;

trait DataEntryNameDescription
{
    /**
     * Returns the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getDataValue('name');
    }



    /**
     * Returns the SEO name for this user
     *
     * @return string|null
     */
    public function getSeoName(): ?string
    {
        return $this->getDataValue('seo_name');
    }



    /**
     * Sets the name for this user
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        $seo_name = Seo::unique($name, $this->table, $this->id);

        $this->setDataValue('name', $name);
        return $this->setDataValue('seo_name', $seo_name);
    }



    /**
     * Returns the description for this user
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getDataValue('description');
    }



    /**
     * Sets the description for this user
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->setDataValue('description', $description);
    }
}